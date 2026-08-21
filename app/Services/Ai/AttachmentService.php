<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiConversation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Subida y preparación de imágenes.
 *
 * Se guardan en un disco PRIVADO con nombre aleatorio: nada de fotos de piezas
 * de clientes accesibles por URL adivinable.
 *
 * Antes de mandarle nada al proveedor de IA se genera una versión optimizada:
 * una foto de 12 MB de un celular no aporta más señal que una de 1280 px, pero
 * cuesta tokens y latencia.
 *
 * @see docs/security.md §"Adjuntos"
 */
final class AttachmentService
{
    /** @return array{ok:bool, attachment:?AiAttachment, error:?string} */
    public function store(AiConversation $conversation, UploadedFile $file): array
    {
        $validation = $this->validate($file);

        if ($validation !== null) {
            return ['ok' => false, 'attachment' => null, 'error' => $validation];
        }

        $disk      = (string) config('bmh.attachments.disk', 'ai_private');
        $extension = $this->safeExtension($file);
        $basename  = Str::uuid()->toString();

        $originalPath = $file->storeAs(
            "conversations/{$conversation->id}",
            "{$basename}.{$extension}",
            $disk,
        );

        if ($originalPath === false) {
            return ['ok' => false, 'attachment' => null, 'error' => 'No se pudo guardar la imagen.'];
        }

        $absolute  = Storage::disk($disk)->path($originalPath);
        $dimensions = @getimagesize($absolute);

        $derived = $this->deriveVersions($disk, $originalPath, $absolute, $basename, $conversation->id);

        $attachment = AiAttachment::query()->create([
            'conversation_id' => $conversation->id,
            'disk'            => $disk,
            'path'            => $originalPath,
            'analysis_path'   => $derived['analysis'],
            'thumbnail_path'  => $derived['thumbnail'],
            'mime'            => $file->getMimeType() ?? 'application/octet-stream',
            'bytes'           => $file->getSize() ?? 0,
            'width'           => $dimensions[0] ?? null,
            'height'          => $dimensions[1] ?? null,
        ]);

        return ['ok' => true, 'attachment' => $attachment, 'error' => null];
    }

    /** Ruta absoluta de la versión que se le manda al modelo. */
    public function analysisPath(AiAttachment $attachment): ?string
    {
        $path = $attachment->analysis_path ?? $attachment->path;

        if ($path === null) {
            return null;
        }

        $absolute = Storage::disk($attachment->disk)->path($path);

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Validación real, no sólo por extensión.
     *
     * Se comprueba el MIME que reporta el contenido y que el archivo sea
     * efectivamente una imagen decodificable: un `.jpg` que en realidad es un
     * PHP no pasa.
     */
    public function validate(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return 'El archivo no se subió correctamente.';
        }

        $maxKb = (int) config('bmh.attachments.max_size_kb', 12288);

        if (($file->getSize() ?? 0) > $maxKb * 1024) {
            return sprintf('La imagen supera el máximo de %d MB.', (int) round($maxKb / 1024));
        }

        $mime    = $file->getMimeType();
        $allowed = (array) config('bmh.attachments.allowed_mimes');

        if ($mime === null || ! in_array($mime, $allowed, true)) {
            return 'Formato no soportado. Mandá una foto JPG, PNG, WEBP o HEIC.';
        }

        // HEIC no siempre es decodificable por GD; se acepta igual y se maneja
        // en la derivación.
        if (! in_array($mime, ['image/heic', 'image/heif'], true) && @getimagesize($file->getPathname()) === false) {
            return 'El archivo no es una imagen válida.';
        }

        return null;
    }

    /**
     * Genera la versión de análisis y el thumbnail.
     *
     * Si GD no está disponible o el formato no se puede decodificar, se degrada
     * a usar el original: preferimos analizar una foto grande antes que no
     * analizar nada.
     *
     * @return array{analysis:?string, thumbnail:?string}
     */
    private function deriveVersions(
        string $disk,
        string $originalPath,
        string $absolute,
        string $basename,
        int $conversationId,
    ): array {
        if (! extension_loaded('gd')) {
            return ['analysis' => $originalPath, 'thumbnail' => null];
        }

        $image = $this->readImage($absolute);

        if ($image === null) {
            return ['analysis' => $originalPath, 'thumbnail' => null];
        }

        // Corrige la orientación EXIF y, de paso, descarta el resto de los
        // metadatos: imagejpeg() no los reescribe. Adiós geolocalización.
        $image = $this->autoOrient($image, $absolute);

        $analysisPath  = "conversations/{$conversationId}/{$basename}_analysis.jpg";
        $thumbnailPath = "conversations/{$conversationId}/{$basename}_thumb.jpg";

        $this->writeResized(
            $image,
            Storage::disk($disk)->path($analysisPath),
            (int) config('bmh.attachments.analysis_max_px', 1280),
            (int) config('bmh.attachments.analysis_quality', 82),
        );

        $this->writeResized(
            $image,
            Storage::disk($disk)->path($thumbnailPath),
            (int) config('bmh.attachments.thumbnail_px', 320),
            75,
        );

        imagedestroy($image);

        return ['analysis' => $analysisPath, 'thumbnail' => $thumbnailPath];
    }

    private function readImage(string $path): ?\GdImage
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    private function autoOrient(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);

        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated instanceof \GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function writeResized(\GdImage $source, string $destination, int $maxSide, int $quality): void
    {
        $width  = imagesx($source);
        $height = imagesy($source);
        $scale  = min(1.0, $maxSide / max($width, $height));

        $targetWidth  = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Fondo blanco: los PNG con transparencia salían con fondo negro y eso
        // le come contraste a la pieza.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        @mkdir(dirname($destination), 0755, true);
        imagejpeg($canvas, $destination, $quality);
        imagedestroy($canvas);
    }

    private function safeExtension(UploadedFile $file): string
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], true)
            ? $extension
            : 'jpg';
    }
}

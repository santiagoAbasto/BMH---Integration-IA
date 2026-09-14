<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeSliderRequest;
use App\Http\Requests\UpdateHomeSliderRequest;
use App\Models\Imagen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class HomeSliderController extends Controller
{
    public function store(StoreHomeSliderRequest $request): RedirectResponse
    {
        [$desktopPath, $tipo] = $this->guardarArchivo($request->file('desktop_image'));

        $slider = new Imagen();
        $slider->path = $desktopPath;
        $slider->path_mobile = $request->hasFile('mobile_image')
            ? $this->guardarArchivo($request->file('mobile_image'))[0]
            : null;
        $slider->tipo = $tipo;
        $slider->sector = 'home-slider';
        $slider->orden = $request->validated('orden');
        $slider->baner_texto = $request->validated('baner_texto');
        $slider->save();

        return back()->with('success', 'Slider creado');
    }

    public function update(UpdateHomeSliderRequest $request, Imagen $imagen): RedirectResponse
    {
        abort_unless($imagen->sector === 'home-slider', 404);

        $archivosAnteriores = [];

        if ($request->hasFile('desktop_image')) {
            $archivosAnteriores[] = $imagen->path;
            [$imagen->path, $imagen->tipo] = $this->guardarArchivo($request->file('desktop_image'));
        }

        if ($request->hasFile('mobile_image')) {
            $archivosAnteriores[] = $imagen->path_mobile;
            $imagen->path_mobile = $this->guardarArchivo($request->file('mobile_image'))[0];
        }

        $imagen->orden = $request->validated('orden');
        $imagen->baner_texto = $request->validated('baner_texto');
        $imagen->save();

        foreach (array_unique(array_filter($archivosAnteriores)) as $archivo) {
            $this->eliminarSiNoSeUsa($archivo);
        }

        return back()->with('success', 'Slider actualizado');
    }

    /** @return array{string, string} */
    private function guardarArchivo(UploadedFile $archivo): array
    {
        $extension = strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());
        $nombre = 'media_'.uniqid().'.'.$extension;

        File::ensureDirectoryExists(public_path('imagenes'));
        $archivo->move(public_path('imagenes'), $nombre);

        return [$nombre, in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'], true) ? 'video' : 'imagen'];
    }

    private function eliminarSiNoSeUsa(string $archivo): void
    {
        $enUso = Imagen::query()
            ->where('path', $archivo)
            ->orWhere('path_mobile', $archivo)
            ->exists();

        if (! $enUso) {
            File::delete(public_path('imagenes/'.$archivo));
        }
    }
}

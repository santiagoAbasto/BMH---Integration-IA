<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use App\Models\ImagenBiblioteca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Biblioteca de imágenes de productos.
 *
 * Cada archivo de public/imagenes que usan los productos tiene un código
 * único y editable (tabla biblioteca_imagenes). Ese código es el que se
 * escribe en la columna IMÁGENES del Excel de productos.
 *
 * Qué producto usa qué imagen sigue en la tabla imagenes (sector producto,
 * por path): esta clase nunca mueve, renombra ni borra archivos en uso.
 */
final class BibliotecaImagenes
{
    /** Separadores de códigos en una celda del Excel. */
    public const SEPARADORES = '/[,;\n]+/';

    /** En la celda IMÁGENES, quita todas las fotos del producto. */
    public const QUITAR = '-';

    public function __construct(private readonly OptimizadorImagenes $optimizador)
    {
    }

    // ------------------------------------------------------------- registro

    /**
     * Registra las imágenes de productos que todavía no tienen código.
     *
     * Para que cada producto quede con códigos «limpios», recorre los
     * productos por código y, dentro de cada uno, la principal primero:
     * la principal recibe el código del producto (NFSO42) y las demás
     * NFSO42-2, NFSO42-3. Un archivo que usan varios productos se registra
     * una vez. Los archivos que no están en el disco no se registran.
     *
     * @return array{registradas: int, ya_estaban: int, sin_archivo: int}
     */
    public function sincronizar(): array
    {
        $registradas = ImagenBiblioteca::query()->pluck('path')
            ->mapWithKeys(fn ($path) => [mb_strtolower((string) $path) => true])->all();
        $codigos = ImagenBiblioteca::query()->pluck('codigo')
            ->mapWithKeys(fn ($codigo) => [(string) $codigo => true])->all();

        $directorio = $this->directorio();
        $nuevas = [];
        $sinArchivo = 0;

        foreach ($this->imagenesDeProductos() as $fila) {
            $clave = mb_strtolower($fila->path);
            if (isset($registradas[$clave])) {
                continue;
            }
            $registradas[$clave] = true;

            // El código se reserva aunque falte el archivo: así, si a un
            // producto le falta la principal, la segunda igual es CODIGO-2.
            $base = CodigoImagen::normalizar($fila->codigo);
            $codigo = $base !== '' ? $this->libreDesde($base, $codigos) : $this->siguienteGenerico($codigos);
            $codigos[$codigo] = true;

            $ruta = $directorio.DIRECTORY_SEPARATOR.$fila->path;
            if (! is_file($ruta)) {
                $sinArchivo++;
                continue;
            }

            $medidas = @getimagesize($ruta);
            $nuevas[] = [
                'codigo' => $codigo,
                'path' => $fila->path,
                'nombre_original' => $fila->path,
                'bytes' => @filesize($ruta) ?: null,
                'ancho' => $medidas[0] ?? null,
                'alto' => $medidas[1] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($nuevas): void {
            foreach (array_chunk($nuevas, 500) as $tanda) {
                DB::table('biblioteca_imagenes')->insert($tanda);
            }
        });

        return ['registradas' => count($nuevas), 'ya_estaban' => count($registradas) - count($nuevas) - $sinArchivo, 'sin_archivo' => $sinArchivo];
    }

    /**
     * Sube una imagen a la biblioteca: la optimiza (WebP sin pérdida visible,
     * ver OptimizadorImagenes) y le da código.
     *
     * @return array{imagen: ImagenBiblioteca, motivo: string}
     */
    public function subir(UploadedFile $archivo): array
    {
        $nombreOriginal = $archivo->getClientOriginalName();
        $path = $this->optimizador->guardarSubida($archivo, $this->directorio());
        [$codigo, $motivo] = $this->sugerirCodigo($nombreOriginal);

        $ruta = $this->directorio().DIRECTORY_SEPARATOR.$path;
        $medidas = @getimagesize($ruta);

        $imagen = ImagenBiblioteca::query()->create([
            'codigo' => $codigo,
            'path' => $path,
            'nombre_original' => mb_substr($nombreOriginal, 0, 255),
            'bytes' => @filesize($ruta) ?: null,
            'ancho' => $medidas[0] ?? null,
            'alto' => $medidas[1] ?? null,
        ]);

        return ['imagen' => $imagen, 'motivo' => $motivo];
    }

    /**
     * Código para una imagen nueva según el nombre del archivo:
     *  - «NFSO42.jpg» (un código de producto) → NFSO42, o NFSO42-2 si ya existe;
     *  - «NFSO42-3.jpg» → NFSO42-3 si está libre;
     *  - una palabra del nombre que es un código de producto («foto NFSO42 frente.jpg») → ese código;
     *  - si no, uno genérico: IMG-0001.
     *
     * @return array{string, string} código y por qué
     */
    public function sugerirCodigo(string $nombreArchivo): array
    {
        $codigos = $this->codigosOcupados();
        $productos = $this->codigosDeProductos();
        $base = CodigoImagen::normalizar(pathinfo($nombreArchivo, PATHINFO_FILENAME));

        if ($base !== '' && isset($productos[$base])) {
            return [$this->libreDesde($base, $codigos), "El nombre del archivo es el código del producto {$base}."];
        }
        if (preg_match('/^(.+)-(\d{1,2})$/', $base, $m) && isset($productos[$m[1]]) && ! isset($codigos[$base])) {
            return [$base, "El nombre del archivo es la foto {$m[2]} del producto {$m[1]}."];
        }
        // Un código de producto dentro del nombre («foto A-101 frente»): el más
        // largo que aparezca entero, para no confundir A-10 con A-101.
        $mencionado = null;
        foreach (array_keys($productos) as $codigoProducto) {
            $codigoProducto = (string) $codigoProducto;
            if (strlen($codigoProducto) >= 3 && preg_match('/\d/', $codigoProducto)
                && str_contains('-'.$base.'-', '-'.$codigoProducto.'-')
                && strlen($codigoProducto) > strlen((string) $mencionado)) {
                $mencionado = $codigoProducto;
            }
        }
        if ($mencionado !== null) {
            return [$this->libreDesde($mencionado, $codigos), "El nombre del archivo menciona el producto {$mencionado}."];
        }

        return [$this->siguienteGenerico($codigos), 'El nombre del archivo no coincide con ningún producto: recibió un código genérico.'];
    }

    /**
     * @throws RuntimeException con un mensaje para mostrar
     */
    public function renombrar(ImagenBiblioteca $imagen, string $nuevo): ImagenBiblioteca
    {
        $nuevo = trim($nuevo);
        $normalizado = CodigoImagen::normalizar($nuevo);

        if ($normalizado === '') {
            throw new RuntimeException('Escribí un código.');
        }
        if ($normalizado !== $nuevo) {
            throw new RuntimeException("Sólo letras, números, guion (-), guion bajo (_) y punto, sin espacios. Por ejemplo: {$normalizado}");
        }
        if ($normalizado !== $imagen->codigo && ImagenBiblioteca::query()->where('codigo', $normalizado)->where('id', '<>', $imagen->id)->exists()) {
            throw new RuntimeException("El código {$normalizado} ya lo tiene otra imagen.");
        }

        $imagen->codigo = $normalizado;
        $imagen->save();

        return $imagen;
    }

    /**
     * Saca la imagen de la biblioteca y borra el archivo. Sólo si nada la usa:
     * ningún producto ni otra parte del sitio (slider, logos, novedades…).
     *
     * @throws RuntimeException si está en uso
     */
    public function eliminar(ImagenBiblioteca $imagen): void
    {
        $usos = $this->usosDe($imagen->path);
        if ($usos !== []) {
            throw new RuntimeException('No se puede eliminar: la usa '.count($usos).' producto(s).');
        }
        if (DB::table('imagenes')->where('path', $imagen->path)->exists()) {
            throw new RuntimeException('No se puede eliminar: la usa otra parte del sitio.');
        }

        $ruta = $this->directorio().DIRECTORY_SEPARATOR.$imagen->path;
        $imagen->delete();
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    // ---------------------------------------------------------------- usos

    /**
     * Qué productos usa cada archivo, con la principal primero.
     *
     * @return array<string, list<array{id: int, codigo: string, nombre: string, rol: string}>> path en minúsculas => usos
     */
    public function usos(): array
    {
        $usos = [];
        foreach ($this->imagenesDeProductos() as $fila) {
            $usos[mb_strtolower($fila->path)][] = [
                'id' => (int) $fila->producto_id,
                'codigo' => (string) $fila->codigo,
                'nombre' => (string) $fila->nombre,
                'rol' => $fila->tipo === 'portada' ? 'principal' : 'galeria',
            ];
        }

        return $usos;
    }

    /** @return list<array{id: int, codigo: string, nombre: string, rol: string}> */
    public function usosDe(string $path, ?array $usos = null): array
    {
        return ($usos ?? $this->usos())[mb_strtolower($path)] ?? [];
    }

    // ----------------------------------------------------------- Excel

    /**
     * Para resolver muchas celdas sin consultar la base en cada una.
     *
     * @return array{porCodigo: array<string, array{path: string, codigo: string}>, porNombre: array<string, array{path: string, codigo: string}>}
     */
    public function indice(): array
    {
        $porCodigo = [];
        $porNombre = [];
        foreach (ImagenBiblioteca::query()->get(['codigo', 'path', 'nombre_original']) as $imagen) {
            $dato = ['path' => $imagen->path, 'codigo' => $imagen->codigo];
            $porCodigo[$imagen->codigo] = $dato;
            foreach ([$imagen->path, (string) $imagen->nombre_original] as $nombre) {
                $clave = self::claveNombre($nombre);
                if ($clave !== '' && ! isset($porNombre[$clave])) {
                    $porNombre[$clave] = $dato;
                }
            }
        }

        return ['porCodigo' => $porCodigo, 'porNombre' => $porNombre];
    }

    /**
     * Resuelve lo escrito en una celda IMÁGENES: códigos de la biblioteca y,
     * para no cortar a nadie, también nombres de archivo o URLs viejas.
     *
     * @return array{imagenes: list<array{path: string, codigo: string}>, desconocidos: list<string>, por_nombre: array<string, string>}
     */
    public function resolverCelda(string $celda, array $indice): array
    {
        $imagenes = [];
        $desconocidos = [];
        $porNombre = [];

        foreach (self::separar($celda) as $escrito) {
            $codigo = CodigoImagen::normalizar($escrito);
            $dato = $indice['porCodigo'][$codigo] ?? null;
            if ($dato === null) {
                $dato = $indice['porNombre'][self::claveNombre($escrito)] ?? null;
                if ($dato !== null) {
                    $porNombre[$escrito] = $dato['codigo'];
                }
            }
            if ($dato === null) {
                $desconocidos[] = $escrito;
                continue;
            }
            if (! in_array($dato['path'], array_column($imagenes, 'path'), true)) {
                $imagenes[] = $dato;
            }
        }

        return ['imagenes' => $imagenes, 'desconocidos' => $desconocidos, 'por_nombre' => $porNombre];
    }

    /** @return list<string> */
    public static function separar(string $celda): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split(self::SEPARADORES, $celda) ?: []),
            fn (string $parte) => $parte !== ''
        ));
    }

    /**
     * Los códigos de imagen de cada producto, principal primero, como van en
     * la columna IMÁGENES: «NFSO42, NFSO42-2».
     *
     * @return array<int, string> producto_id => celda
     */
    public function celdasPorProducto(): array
    {
        $codigoPorPath = ImagenBiblioteca::query()->pluck('codigo', 'path')
            ->mapWithKeys(fn ($codigo, $path) => [mb_strtolower((string) $path) => $codigo])->all();

        $celdas = [];
        foreach ($this->imagenesDeProductos() as $fila) {
            $codigo = $codigoPorPath[mb_strtolower($fila->path)] ?? null;
            if ($codigo !== null) {
                $celdas[(int) $fila->producto_id][] = $codigo;
            }
        }

        return array_map(fn (array $codigos) => implode(', ', array_unique($codigos)), $celdas);
    }

    // ------------------------------------------------------------- privado

    /**
     * Imágenes de productos con el código y nombre del producto, por código
     * de producto y, dentro de cada uno, principal primero y luego el orden.
     */
    private function imagenesDeProductos(): Collection
    {
        return DB::table('imagenes as i')
            ->join('productos as p', 'p.id', '=', 'i.producto_id')
            ->where('i.sector', 'producto')
            ->whereNotNull('i.path')
            ->where('i.path', '<>', '')
            ->orderBy('p.codigo')
            ->orderBy('p.id')
            ->orderByRaw("CASE WHEN i.tipo = 'portada' THEN 0 ELSE 1 END")
            ->orderBy('i.orden')
            ->orderBy('i.id')
            ->get(['i.path', 'i.tipo', 'i.producto_id', 'p.codigo', 'p.nombre']);
    }

    /** El código mismo si está libre; si no, CODIGO-2, CODIGO-3… */
    private function libreDesde(string $base, array $ocupados): string
    {
        if (! isset($ocupados[$base])) {
            return $base;
        }
        for ($n = 2; ; $n++) {
            $sufijo = '-'.$n;
            $candidato = mb_substr($base, 0, CodigoImagen::LARGO_MAXIMO - strlen($sufijo)).$sufijo;
            if (! isset($ocupados[$candidato])) {
                return $candidato;
            }
        }
    }

    private function siguienteGenerico(array $ocupados): string
    {
        $maximo = 0;
        foreach (array_keys($ocupados) as $codigo) {
            if (str_starts_with((string) $codigo, CodigoImagen::PREFIJO_GENERICO)) {
                $maximo = max($maximo, (int) substr((string) $codigo, strlen(CodigoImagen::PREFIJO_GENERICO)));
            }
        }

        return CodigoImagen::PREFIJO_GENERICO.str_pad((string) ($maximo + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<string, true> */
    private function codigosOcupados(): array
    {
        return ImagenBiblioteca::query()->pluck('codigo')->mapWithKeys(fn ($c) => [(string) $c => true])->all();
    }

    /** @return array<string, true> códigos de producto normalizados */
    private function codigosDeProductos(): array
    {
        $codigos = [];
        foreach (DB::table('productos')->whereNotNull('codigo')->pluck('codigo') as $codigo) {
            $normalizado = CodigoImagen::normalizar((string) $codigo);
            if ($normalizado !== '') {
                $codigos[$normalizado] = true;
            }
        }

        return $codigos;
    }

    /** «media_x.png», «https://…/A-101.png» y «a-101.PNG» dan la misma clave. */
    private static function claveNombre(string $nombre): string
    {
        $base = basename(str_replace('\\', '/', trim($nombre)));

        return mb_strtolower((string) preg_replace('/\.(png|jpe?g|webp|gif)$/i', '', $base));
    }

    private function directorio(): string
    {
        return rtrim((string) config('imagenes.directorio'), '/\\');
    }
}

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
 * único y editable (tabla biblioteca_imagenes). El código dice de qué
 * producto es la foto: NFSO42 es la principal de NFSO42 y NFSO42-2 la
 * segunda. Por eso, subir «NFSO42.jpg» ya se la asigna (subir()), y
 * corregir el código de una imagen sin asignar también (renombrar()).
 * El Excel de productos usa los mismos códigos en la columna IMÁGENES.
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

    /** Resultados de subir(). */
    public const ASIGNADA = 'asignada';

    public const SIN_PRODUCTO = 'sin_producto';

    public const CONFLICTO = 'conflicto';

    public const REEMPLAZADA = 'reemplazada';

    /** @var array<string, list<array{id: int, codigo: string, nombre: string}>>|null */
    private ?array $productosPorCodigo = null;

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
     * Sube una imagen: el nombre del archivo es su código y, si es el de un
     * producto, la foto se le asigna sola:
     *  - «NFSO42.jpg» → la foto principal de NFSO42;
     *  - «NFSO42-2.jpg» → su segunda foto (NFSO42-3 la tercera…).
     *
     * Si ya hay una imagen con ese código no se guarda nada y se avisa
     * (CONFLICTO), salvo que se elija qué hacer (ver colocar()):
     *  - $modo «reemplazar»: la nueva ocupa el lugar de la foto $objetivo
     *    (por defecto, la que tiene el código). El archivo anterior queda en el disco;
     *  - $modo «agregar»: se agrega en la $posicion elegida (por defecto, al final).
     *
     * @param  'reemplazar'|'agregar'|null  $modo
     * @return array{resultado: string, imagen: ImagenBiblioteca|null, destino: array|null, existente: ImagenBiblioteca|null, sugerencia: string|null}
     *         resultado: ASIGNADA, SIN_PRODUCTO, CONFLICTO o REEMPLAZADA
     */
    public function subir(UploadedFile $archivo, ?string $modo = null, ?string $objetivo = null, ?int $posicion = null): array
    {
        $nombreOriginal = $archivo->getClientOriginalName();
        $base = CodigoImagen::normalizar(pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $existente = $base !== '' ? ImagenBiblioteca::query()->where('codigo', $base)->first() : null;
        $resultado = ['resultado' => self::ASIGNADA, 'imagen' => null, 'destino' => null, 'existente' => $existente, 'sugerencia' => null];

        if ($existente !== null && $modo === null) {
            return ['resultado' => self::CONFLICTO, 'destino' => $this->destinoDe($base)] + $resultado;
        }

        // El código es de un producto: se elige el lugar entre sus fotos.
        $destinoBase = $existente !== null ? $this->destinoDe($base) : null;
        if ($destinoBase !== null) {
            $imagen = ImagenBiblioteca::query()->create(['codigo' => $this->codigoLibre($destinoBase['codigo'])] + $this->guardarArchivo($archivo));
            $destino = $this->colocar($imagen, $destinoBase, (string) $modo, $objetivo ?? $existente->path, $posicion);

            return ['resultado' => $destino['reemplazo'] ? self::REEMPLAZADA : self::ASIGNADA, 'imagen' => $imagen, 'destino' => $destino] + $resultado;
        }

        if ($existente !== null && $modo === 'reemplazar') {
            $imagen = $this->reemplazar($existente, $archivo);

            return ['resultado' => self::REEMPLAZADA, 'imagen' => $imagen, 'destino' => $this->destinoDe($imagen->codigo)] + $resultado;
        }

        $ocupados = $this->codigosOcupados();
        if ($base === '') {
            $codigo = $this->siguienteGenerico($ocupados);
        } elseif ($existente !== null) {
            // «Agregar como otra foto»: la próxima libre del mismo producto.
            $codigo = $this->libreDesde($this->destinoDe($base)['codigo'] ?? $base, $ocupados);
        } else {
            $codigo = $base;
        }

        $imagen = ImagenBiblioteca::query()->create(['codigo' => $codigo] + $this->guardarArchivo($archivo));
        $destino = $this->asignar($imagen);

        if ($destino === null) {
            return ['resultado' => self::SIN_PRODUCTO, 'imagen' => $imagen, 'sugerencia' => $this->sugerencia($codigo)] + $resultado;
        }

        return ['imagen' => $imagen, 'destino' => $destino] + $resultado;
    }

    /**
     * El producto y la posición de la foto que indica un código de imagen:
     * «NFSO42» → la principal (1) de NFSO42; «NFSO42-2» → su segunda foto.
     * Si el código de producto está repetido, son todos los que lo tienen.
     *
     * @return array{codigo: string, posicion: int, productos: list<array{id: int, codigo: string, nombre: string}>}|null
     */
    public function destinoDe(string $codigo): ?array
    {
        $productos = $this->productosPorCodigo();

        if (isset($productos[$codigo])) {
            return ['codigo' => $codigo, 'posicion' => 1, 'productos' => $productos[$codigo]];
        }
        if (preg_match('/^(.+)-(\d{1,2})$/', $codigo, $m) === 1 && (int) $m[2] >= 2 && isset($productos[$m[1]])) {
            return ['codigo' => $m[1], 'posicion' => (int) $m[2], 'productos' => $productos[$m[1]]];
        }

        return null;
    }

    /**
     * Pone la imagen en el producto que indica su código, en su posición
     * (la principal pasa a ser la segunda si ya había una). Las demás fotos
     * del producto quedan.
     *
     * @return array{codigo: string, posicion: int, productos: list<array{id: int, codigo: string, nombre: string}>}|null null si el código no es de ningún producto
     */
    public function asignar(ImagenBiblioteca $imagen): ?array
    {
        $destino = $this->destinoDe($imagen->codigo);
        if ($destino === null) {
            return null;
        }

        DB::transaction(function () use ($destino, $imagen): void {
            foreach ($destino['productos'] as $producto) {
                $this->ponerFoto($producto['id'], $imagen->path, $destino['posicion']);
            }
            $this->ordenarCodigos($destino['productos'][0]['id'], $destino['codigo']);
        });
        $imagen->refresh();
        $destino['posicion'] = $this->posicionEn($destino['productos'][0]['id'], $imagen->path) ?? $destino['posicion'];

        return $destino;
    }

    /**
     * Pone la imagen entre las fotos del producto del destino, como se eligió:
     *  - «reemplazar»: ocupa el lugar de la foto $objetivo (un path de sus
     *    fotos), que sale del producto; su archivo queda en el disco;
     *  - «agregar»: va en la $posicion (1 = principal; null = al final) y
     *    las demás se corren.
     * El código pasa a ser el de su lugar (NFSO42, NFSO42-2…) si está libre.
     *
     * @return array{codigo: string, posicion: int, productos: list<array{id: int, codigo: string, nombre: string}>, reemplazo: bool}
     *
     * @throws RuntimeException si la foto a reemplazar ya no está en el producto
     */
    public function colocar(ImagenBiblioteca $imagen, array $destino, string $modo, ?string $objetivo, ?int $posicion): array
    {
        $fotos = array_column($this->fotosDe($destino['productos'][0]['id']), 'path');

        if ($modo === 'reemplazar') {
            $indice = $objetivo !== null ? array_search($objetivo, $fotos, true) : false;
            if ($indice === false) {
                throw new RuntimeException('Esa foto ya no está en el producto. Recargá la página y probá de nuevo.');
            }
            $posicion = $indice + 1;

            DB::transaction(function () use ($imagen, $destino, $objetivo, $posicion): void {
                foreach ($destino['productos'] as $producto) {
                    DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $producto['id'])->where('path', $objetivo)
                        ->update(['path' => $imagen->path, 'updated_at' => now()]);
                }
                // Si ya nadie la usa, la anterior deja la biblioteca y le pasa el código.
                $anterior = ImagenBiblioteca::query()->where('path', $objetivo)->first();
                $codigo = null;
                if ($anterior !== null && ! DB::table('imagenes')->where('path', $objetivo)->exists()) {
                    $codigo = $anterior->codigo;
                    $anterior->delete();
                }
                $imagen->codigo = $codigo ?? $this->codigoPara($imagen, $destino['codigo'], $posicion);
                $imagen->save();
                $this->ordenarCodigos($destino['productos'][0]['id'], $destino['codigo']);
            });
            $imagen->refresh();

            return ['posicion' => $posicion, 'reemplazo' => true] + $destino;
        }

        $posicion = $posicion === null ? count($fotos) + 1 : max(1, min($posicion, count($fotos) + 1));

        DB::transaction(function () use ($imagen, $destino, $posicion): void {
            $imagen->codigo = $this->codigoPara($imagen, $destino['codigo'], $posicion);
            $imagen->save();
            foreach ($destino['productos'] as $producto) {
                $this->ponerFoto($producto['id'], $imagen->path, $posicion);
            }
            $this->ordenarCodigos($destino['productos'][0]['id'], $destino['codigo']);
        });
        $imagen->refresh();

        return ['posicion' => $posicion, 'reemplazo' => false] + $destino;
    }

    /**
     * Las fotos de un producto en orden (la principal primero), con su código.
     *
     * @return list<array{path: string, codigo: string|null, url: string, posicion: int}>
     */
    public function fotosDe(int $productoId): array
    {
        $paths = DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $productoId)
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')->orderBy('id')
            ->pluck('path')->all();
        $codigos = ImagenBiblioteca::query()->whereIn('path', $paths)->pluck('codigo', 'path')->all();

        return array_map(fn (string $path, int $i) => [
            'path' => $path,
            'codigo' => $codigos[$path] ?? null,
            'url' => asset('imagenes/'.$path),
            'posicion' => $i + 1,
        ], $paths, array_keys($paths));
    }

    /** El código mismo si nadie lo tiene; si no, el siguiente libre: NFSO42-2, NFSO42-3… */
    public function codigoLibre(string $base): string
    {
        return $this->libreDesde($base, $this->codigosOcupados());
    }

    /**
     * Para un código que no es de ningún producto, el que probablemente se
     * quiso escribir: uno que aparece entero en el nombre («foto NFSO42
     * frente», «NFSO42 (1)») o uno con una sola letra de diferencia.
     */
    public function sugerencia(string $codigo): ?string
    {
        $mencionado = null;
        $parecido = null;
        foreach (array_keys($this->productosPorCodigo()) as $codigoProducto) {
            $codigoProducto = (string) $codigoProducto;
            if (strlen($codigoProducto) >= 3 && preg_match('/\d/', $codigoProducto)
                && str_contains('-'.$codigo.'-', '-'.$codigoProducto.'-')
                && strlen($codigoProducto) > strlen((string) $mencionado)) {
                $mencionado = $codigoProducto;
            }
            if ($parecido === null && strlen($codigoProducto) >= 4 && abs(strlen($codigoProducto) - strlen($codigo)) <= 1
                && levenshtein($codigoProducto, $codigo) === 1) {
                $parecido = $codigoProducto;
            }
        }

        return $mencionado ?? $parecido;
    }

    /**
     * Cambia el código. Si la imagen no está en ningún producto y el código
     * nuevo es el de uno, se le asigna (así se corrige una subida con un
     * nombre equivocado). Una imagen en uso sólo cambia de código.
     *
     * @return array{codigo: string, posicion: int, productos: list<array{id: int, codigo: string, nombre: string}>}|null a qué producto se asignó
     *
     * @throws RuntimeException con un mensaje para mostrar
     */
    public function renombrar(ImagenBiblioteca $imagen, string $nuevo, ?string $modo = null, ?string $objetivo = null, ?int $posicion = null): ?array
    {
        $nuevo = trim($nuevo);
        $normalizado = CodigoImagen::normalizar($nuevo);

        if ($normalizado === '') {
            throw new RuntimeException('Escribí un código.');
        }
        if ($normalizado !== $nuevo) {
            throw new RuntimeException("Sólo letras, números, guion (-), guion bajo (_) y punto, sin espacios. Por ejemplo: {$normalizado}");
        }
        $ocupante = $normalizado !== $imagen->codigo
            ? ImagenBiblioteca::query()->where('codigo', $normalizado)->where('id', '<>', $imagen->id)->first()
            : null;
        if ($ocupante !== null) {
            $destino = $this->destinoDe($normalizado);
            // Una imagen sin asignar puede ir a cualquier lugar del producto:
            // se pregunta si reemplazar esa foto o dónde agregarla.
            if ($destino !== null && $this->usosDe($imagen->path) === []) {
                if ($modo === null) {
                    throw new CodigoOcupado($normalizado, $destino, $ocupante->path);
                }

                return $this->colocar($imagen, $destino, $modo, $objetivo ?? $ocupante->path, $posicion);
            }
            throw new RuntimeException($destino === null
                ? "El código {$normalizado} ya lo tiene otra imagen."
                : "El código {$normalizado} ya lo tiene otra foto de ese producto. Para sumarla como otra foto, usá {$this->codigoLibre($destino['codigo'])}.");
        }

        $imagen->codigo = $normalizado;
        $imagen->save();

        return $this->usosDe($imagen->path) === [] ? $this->asignar($imagen) : null;
    }

    /**
     * Saca la imagen de la biblioteca y borra el archivo.
     *
     * Si está en productos, sólo con $quitarDeProductos (quien la elimina ya
     * vio en cuáles): se saca de esos productos y la foto siguiente pasa a
     * ser la principal. Si la usa otra parte del sitio (slider, logos,
     * novedades…) no se elimina.
     *
     * @throws RuntimeException si no se puede eliminar
     */
    public function eliminar(ImagenBiblioteca $imagen, bool $quitarDeProductos = false): void
    {
        $usos = $this->usosDe($imagen->path);
        if ($usos !== [] && ! $quitarDeProductos) {
            throw new RuntimeException('Está en '.count($usos).' producto(s): confirmá que querés sacarla de ahí.');
        }
        $otrasPartes = DB::table('imagenes')->where('path', $imagen->path)
            ->where(fn ($q) => $q->whereNull('sector')->orWhere('sector', '<>', 'producto'))->exists();
        if ($otrasPartes) {
            throw new RuntimeException('No se puede eliminar: la usa otra parte del sitio (slider, logos, novedades…).');
        }

        DB::transaction(function () use ($imagen, $usos): void {
            DB::table('imagenes')->where('sector', 'producto')->where('path', $imagen->path)->delete();
            $imagen->delete();
            foreach ($usos as $uso) {
                $this->renumerar($uso['id']);
                $this->ordenarCodigos($uso['id'], CodigoImagen::normalizar($uso['codigo']));
            }
        });

        $ruta = $this->directorio().DIRECTORY_SEPARATOR.$imagen->path;
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

    /**
     * Que el código diga el lugar: la principal NFSO42, la segunda NFSO42-2…
     * Sólo cambia las fotos que ya tienen un código de este producto y que no
     * usa otro producto; si el código que le tocaría lo tiene otra imagen, la deja.
     */
    private function ordenarCodigos(int $productoId, string $codigoProducto): void
    {
        $usos = $this->usos();
        $cambios = [];
        foreach ($this->fotosDe($productoId) as $foto) {
            if ($foto['codigo'] === null || ($this->destinoDe($foto['codigo'])['codigo'] ?? null) !== $codigoProducto) {
                continue;
            }
            $deOtros = array_filter($usos[mb_strtolower($foto['path'])] ?? [], fn (array $u) => $u['id'] !== $productoId);
            $deSuLugar = $foto['posicion'] === 1 ? $codigoProducto : $codigoProducto.'-'.$foto['posicion'];
            if ($deOtros === [] && $deSuLugar !== $foto['codigo']) {
                $cambios[$foto['path']] = $deSuLugar;
            }
        }
        if ($cambios === []) {
            return;
        }

        $ocupadosPorOtras = ImagenBiblioteca::query()->whereIn('codigo', array_values($cambios))
            ->whereNotIn('path', array_keys($cambios))->pluck('codigo')->all();
        $cambios = array_filter($cambios, fn (string $codigo) => ! in_array($codigo, $ocupadosPorOtras, true));

        // En dos pasos, para poder intercambiar códigos sin chocar con el índice único.
        foreach (array_keys($cambios) as $i => $path) {
            ImagenBiblioteca::query()->where('path', $path)->update(['codigo' => '~'.$i.'~'.uniqid()]);
        }
        foreach ($cambios as $path => $codigo) {
            ImagenBiblioteca::query()->where('path', $path)->update(['codigo' => $codigo, 'updated_at' => now()]);
        }
    }

    private function posicionEn(int $productoId, string $path): ?int
    {
        foreach ($this->fotosDe($productoId) as $foto) {
            if ($foto['path'] === $path) {
                return $foto['posicion'];
            }
        }

        return null;
    }

    /** Vuelve a numerar las fotos del producto: la primera es la principal. */
    private function renumerar(int $productoId): void
    {
        $fotos = DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $productoId)
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')->orderBy('id')
            ->pluck('id');
        foreach ($fotos as $i => $id) {
            DB::table('imagenes')->where('id', $id)->update(['tipo' => $i === 0 ? 'portada' : 'imagen', 'orden' => self::orden($i), 'updated_at' => now()]);
        }
    }

    /**
     * El código de una foto en la posición de un producto: el de su lugar
     * (NFSO42, NFSO42-3) si está libre, el que ya tenía si ya era de ese
     * producto, o el próximo libre.
     */
    private function codigoPara(ImagenBiblioteca $imagen, string $codigoProducto, int $posicion): string
    {
        $ocupados = $this->codigosOcupados();
        unset($ocupados[$imagen->codigo]);
        $deSuLugar = $posicion === 1 ? $codigoProducto : $codigoProducto.'-'.$posicion;

        return match (true) {
            ! isset($ocupados[$deSuLugar]) => $deSuLugar,
            ($this->destinoDe($imagen->codigo)['codigo'] ?? null) === $codigoProducto => $imagen->codigo,
            default => $this->libreDesde($codigoProducto, $ocupados),
        };
    }

    /** El valor de imagenes.orden para cada posición: aa, ab, ac… */
    public static function orden(int $posicion): string
    {
        $posicion = max(0, min($posicion, 675));

        return chr(97 + intdiv($posicion, 26)).chr(97 + $posicion % 26);
    }

    // ------------------------------------------------------------- privado

    /**
     * La imagen nueva toma el lugar de la anterior: mismo código y mismas
     * posiciones en los productos. El archivo anterior no se borra.
     */
    private function reemplazar(ImagenBiblioteca $existente, UploadedFile $archivo): ImagenBiblioteca
    {
        $datos = $this->guardarArchivo($archivo);
        $anterior = $existente->path;

        DB::transaction(function () use ($existente, $datos, $anterior): void {
            DB::table('imagenes')->where('sector', 'producto')->where('path', $anterior)
                ->update(['path' => $datos['path'], 'updated_at' => now()]);
            $existente->fill($datos)->save();
        });

        if ($this->usosDe($existente->path) === []) {
            $this->asignar($existente);
        }

        return $existente;
    }

    /**
     * Optimiza y guarda el archivo (WebP sin pérdida visible, ver OptimizadorImagenes).
     *
     * @return array{path: string, nombre_original: string, bytes: int|null, ancho: int|null, alto: int|null}
     */
    private function guardarArchivo(UploadedFile $archivo): array
    {
        $path = $this->optimizador->guardarSubida($archivo, $this->directorio());
        $ruta = $this->directorio().DIRECTORY_SEPARATOR.$path;
        $medidas = @getimagesize($ruta);

        return [
            'path' => $path,
            'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
            'bytes' => @filesize($ruta) ?: null,
            'ancho' => $medidas[0] ?? null,
            'alto' => $medidas[1] ?? null,
        ];
    }

    /**
     * Pone el archivo en la posición indicada (1 = principal) de las fotos
     * del producto, corriendo las demás. Si ya la tenía, sólo la mueve.
     */
    private function ponerFoto(int $productoId, string $path, int $posicion): void
    {
        $fotos = DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $productoId)
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')->orderBy('id')
            ->get(['id', 'path'])->all();

        $propia = null;
        foreach ($fotos as $i => $foto) {
            if ($foto->path === $path) {
                $propia = $foto;
                unset($fotos[$i]);
            }
        }
        $fotos = array_values($fotos);
        array_splice($fotos, min($posicion - 1, count($fotos)), 0, [$propia ?? (object) ['id' => null, 'path' => $path]]);

        $ahora = now();
        foreach ($fotos as $i => $foto) {
            $datos = ['tipo' => $i === 0 ? 'portada' : 'imagen', 'orden' => self::orden($i), 'updated_at' => $ahora];
            if ($foto->id === null) {
                DB::table('imagenes')->insert($datos + ['path' => $path, 'sector' => 'producto', 'producto_id' => $productoId, 'created_at' => $ahora]);
            } else {
                DB::table('imagenes')->where('id', $foto->id)->update($datos);
            }
        }
    }

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

    /** @return array<string, list<array{id: int, codigo: string, nombre: string}>> código normalizado => productos */
    private function productosPorCodigo(): array
    {
        if ($this->productosPorCodigo !== null) {
            return $this->productosPorCodigo;
        }

        $productos = [];
        foreach (DB::table('productos')->whereNotNull('codigo')->orderBy('id')->get(['id', 'codigo', 'nombre']) as $producto) {
            $normalizado = CodigoImagen::normalizar((string) $producto->codigo);
            if ($normalizado !== '') {
                $productos[$normalizado][] = ['id' => (int) $producto->id, 'codigo' => (string) $producto->codigo, 'nombre' => (string) $producto->nombre];
            }
        }

        return $this->productosPorCodigo = $productos;
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

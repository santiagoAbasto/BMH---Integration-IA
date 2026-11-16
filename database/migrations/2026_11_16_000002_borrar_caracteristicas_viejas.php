<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borra las características viejas, ya pasadas a las tablas relacionales (2026_11_12_000010_normalizar_caracteristicas):
 *  - las 78 columnas `columna_N` de `categorias` (nombre) y de `productos` (valor);
 *  - las columnas fijas de rodamientos de `productos` (diametroInterno, diametroExterno, anchoBanda, tolerancia,
 *    blindaje), vacías y reemplazadas por características;
 *  - las tablas del sistema anterior `categoria_caracteristica` y `producto_caracteristica`.
 *
 * Decisión del usuario (2026-11-16): se borran con sus datos; el respaldo es la base de producción.
 *
 * Por seguridad no corre si las tablas nuevas están vacías: así nunca se borra lo viejo antes de haberlo migrado.
 * down() vuelve a crear la estructura vacía (los datos no vuelven: se restauran desde el respaldo).
 */
return new class extends Migration
{
    private const COLUMNAS_RODAMIENTOS = ['diametroInterno', 'diametroExterno', 'anchoBanda', 'tolerancia', 'blindaje'];

    public function up(): void
    {
        $habiaValores = $this->columnas('productos') !== [] || Schema::hasTable('producto_caracteristica');
        if ($habiaValores && ! DB::table('caracteristica_producto')->exists()) {
            throw new RuntimeException('caracteristica_producto está vacía: corré primero la migración de características (2026_11_12_000010) antes de borrar las columnas viejas.');
        }

        foreach (['productos', 'categorias'] as $tabla) {
            $columnas = $this->columnas($tabla);
            if ($tabla === 'productos') {
                $columnas = array_merge($columnas, array_values(array_filter(self::COLUMNAS_RODAMIENTOS, fn ($c) => Schema::hasColumn('productos', $c))));
            }
            if ($columnas !== []) {
                Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn($columnas));
            }
        }

        Schema::dropIfExists('producto_caracteristica');
        Schema::dropIfExists('categoria_caracteristica');
    }

    public function down(): void
    {
        foreach (['productos', 'categorias'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla): void {
                for ($n = 1; $n <= 78; $n++) {
                    if (! Schema::hasColumn($tabla, 'columna_'.$n)) {
                        $table->string('columna_'.$n)->nullable();
                    }
                }
            });
        }
        Schema::table('productos', function (Blueprint $table): void {
            foreach (self::COLUMNAS_RODAMIENTOS as $columna) {
                if (! Schema::hasColumn('productos', $columna)) {
                    $table->string($columna)->nullable();
                }
            }
        });

        if (! Schema::hasTable('categoria_caracteristica')) {
            Schema::create('categoria_caracteristica', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedBigInteger('categoria_id');
                $table->integer('caracteristica_id');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }
        if (! Schema::hasTable('producto_caracteristica')) {
            Schema::create('producto_caracteristica', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedBigInteger('producto_id');
                $table->integer('caracteristica_id');
                $table->string('valor', 50)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }
    }

    /** @return list<string> Los nombres reales de las columnas columna_N (en categorias hay una «Columna_39»). */
    private function columnas(string $tabla): array
    {
        return array_values(array_filter(Schema::getColumnListing($tabla), fn (string $c) => preg_match('/^columna_\d+$/i', $c) === 1));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos bloques de la Home que pasan a editarse desde el admin (Home → Trabajamos con / Sección Nosotros):
 *
 * - `marcas_aliadas`: los logos de «Trabajamos con» (clientes o proveedores). Arranca con los cuatro que hoy están
 *   fijos en la vista. `logo` es la ruta desde la raíz web: los de fábrica viven en img/rediseno/ y los que se suben
 *   desde el admin, en imagenes/.
 * - `home_nosotros`: el bloque «Más de 55 años…» (dato único). Arranca con lo que la Home muestra hoy: la imagen de
 *   imagenes (sector nosotros-slider) y su texto, o el del Figma si el guardado está vacío o es sólo «BMH».
 *
 * Aditiva: no toca la tabla imagenes (la fila vieja queda como estaba).
 */
return new class extends Migration
{
    private const TITULO_FIGMA = 'Más de 55 años fabricando calidad para el automotor';

    private const TEXTO_FIGMA = '<p>B.M.H Bobinajes San Francisco S.R.L. se dedica a la elaboración y reparación de rotores y estatores para alternador e inducidos para motores de arranque. En 2017 inauguramos nuestra planta de 800 m², equipada con maquinaria automatizada de última generación.</p>';

    public function up(): void
    {
        if (! Schema::hasTable('marcas_aliadas')) {
            Schema::create('marcas_aliadas', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 120);
                $table->string('logo', 255);
                $table->string('url', 500)->nullable();
                $table->string('orden', 2)->default('aa');
                $table->boolean('visible')->default(true);
                $table->timestamps();
                $table->index(['visible', 'orden']);
            });

            $ahora = now();
            foreach (['aa' => ['Pars', 'pars'], 'ab' => ['Divaio', 'divaio'], 'ac' => ['Prestolite', 'prestolite'], 'ad' => ['EP', 'ep']] as $orden => [$nombre, $archivo]) {
                DB::table('marcas_aliadas')->insert([
                    'nombre' => $nombre,
                    'logo' => "img/rediseno/marca-{$archivo}.png",
                    'orden' => $orden,
                    'visible' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }

        if (! Schema::hasTable('home_nosotros')) {
            Schema::create('home_nosotros', function (Blueprint $table): void {
                $table->id();
                $table->string('imagen', 255)->nullable();
                $table->string('titulo', 160);
                $table->text('descripcion')->nullable();
                $table->string('boton_texto', 40)->nullable();
                $table->string('boton_url', 500)->nullable();
                $table->timestamps();
            });

            $vieja = Schema::hasTable('imagenes')
                ? DB::table('imagenes')->where('sector', 'nosotros-slider')->orderBy('orden')->first()
                : null;
            $titulo = trim((string) ($vieja->baner_texto ?? ''));
            $texto = (string) ($vieja->baner_texto_2 ?? '');

            DB::table('home_nosotros')->insert([
                'imagen' => ($vieja->path ?? null) && ($vieja->tipo ?? 'imagen') === 'imagen' ? 'imagenes/'.$vieja->path : null,
                'titulo' => mb_strlen($titulo) >= 10 ? $titulo : self::TITULO_FIGMA,
                'descripcion' => mb_strlen(trim(strip_tags($texto))) >= 20 ? $texto : self::TEXTO_FIGMA,
                'boton_texto' => 'Conocé más',
                'boton_url' => '/nosotros',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_nosotros');
        Schema::dropIfExists('marcas_aliadas');
    }
};

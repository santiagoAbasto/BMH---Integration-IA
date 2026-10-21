<?php

use App\Models\Apariencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apariencia editable del header y el footer del sitio (Extras en el admin).
     *
     * Es una sola fila. Las columnas y sus valores por defecto salen de
     * Apariencia::SETS, que reproduce exactamente los colores que hoy están
     * fijos en public/css/styles2.css: correr la migración no cambia nada
     * visible hasta que alguien edite desde el admin.
     *
     * El header son cinco estados independientes —Home en reposo y con scroll,
     * páginas internas en reposo y con scroll, y celular—, cada uno con su
     * juego completo de colores y su elección de logo.
     *
     * Los logos no viven acá: siguen en `imagenes` (sectores `logo`,
     * `logo-header-blanco` y `logo2`) porque el login y el sidebar del admin
     * ya los leen de ahí.
     */
    public function up(): void
    {
        $defaults = Apariencia::defaults();

        Schema::create('apariencia', function (Blueprint $table) use ($defaults): void {
            $table->id();

            foreach (Apariencia::SETS as $set => $config) {
                foreach (Apariencia::coloresDe($set) as $color) {
                    $campo = Apariencia::campo($set, $color);
                    $table->string($campo, 7)->default($defaults[$campo]);
                }

                $logo = Apariencia::campo($set, 'logo');
                $table->string($logo, 20)->default($defaults[$logo]);
            }

            foreach (Apariencia::CAMPOS_FOOTER as $campo) {
                $table->string($campo, 7)->default($defaults[$campo]);
            }

            $table->timestamps();
        });

        DB::table('apariencia')->insert(['created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('apariencia');
    }
};

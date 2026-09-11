<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apariencia editable del header y el footer del sitio (Extras en el admin).
     *
     * Es una sola fila. Los valores por defecto reproducen exactamente los
     * colores que hoy están fijos en public/css/styles2.css, así que correr la
     * migración no cambia nada visible hasta que alguien edite desde el admin.
     *
     * Los logos no viven acá: siguen en `imagenes` (sectores `logo`,
     * `logo-header-blanco` y `logo2`) porque el favicon, el login y el sidebar
     * del admin ya los leen de ahí.
     */
    public function up(): void
    {
        Schema::create('apariencia', function (Blueprint $table): void {
            $table->id();

            // Header en desktop al hacer scroll (todas las páginas).
            $table->string('header_scroll_fondo', 7)->default('#0098DA');
            $table->string('header_scroll_links', 7)->default('#FFFFFF');
            $table->string('header_scroll_boton_texto', 7)->default('#FFFFFF');
            $table->string('header_scroll_boton_borde', 7)->default('#FFFFFF');
            $table->string('header_scroll_boton_hover_fondo', 7)->default('#0098DA');
            $table->string('header_scroll_boton_hover_texto', 7)->default('#FFFFFF');
            $table->string('header_scroll_logo', 20)->default('transparente');

            // Header en mobile (siempre igual, no cambia con el scroll).
            $table->string('header_mobile_fondo', 7)->default('#FFFFFF');
            $table->string('header_mobile_links', 7)->default('#000000');
            $table->string('header_mobile_boton_texto', 7)->default('#0098DA');
            $table->string('header_mobile_boton_borde', 7)->default('#0098DA');
            $table->string('header_mobile_boton_hover_fondo', 7)->default('#0098DA');
            $table->string('header_mobile_boton_hover_texto', 7)->default('#FFFFFF');
            $table->string('header_mobile_logo', 20)->default('blanco');

            // Footer (igual en todas las páginas).
            $table->string('footer_fondo', 7)->default('#0098DA');
            $table->string('footer_texto', 7)->default('#FFFFFF');
            $table->string('footer_texto_hover', 7)->default('#201E1E');

            $table->timestamps();
        });

        DB::table('apariencia')->insert(['created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('apariencia');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Metadatos SEO (como en Conelec). Aditiva: `metadatos` sigue guardando lo propio de cada página
 * (una fila por sección; un campo vacío usa el valor sugerido de App\Support\Seo\SeoPages) y suma el título.
 * `seo_settings` guarda, en una sola fila, la imagen para compartir y la verificación de buscadores.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('metadatos', 'titulo')) {
            Schema::table('metadatos', function (Blueprint $table): void {
                $table->string('titulo', 70)->nullable()->after('seccion');
            });
        }

        if (! Schema::hasTable('seo_settings')) {
            Schema::create('seo_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('google_verificacion', 255)->nullable();
                $table->string('bing_verificacion', 255)->nullable();
                $table->string('imagen_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');

        if (Schema::hasColumn('metadatos', 'titulo')) {
            Schema::table('metadatos', function (Blueprint $table): void {
                $table->dropColumn('titulo');
            });
        }
    }
};

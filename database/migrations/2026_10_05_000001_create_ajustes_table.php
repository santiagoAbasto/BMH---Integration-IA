<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ajustes sueltos del sitio (clave → valor), editables desde el admin. */
    public function up(): void
    {
        if (Schema::hasTable('ajustes')) {
            return;
        }

        Schema::create('ajustes', function (Blueprint $table): void {
            $table->string('clave', 80)->primary();
            $table->string('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};

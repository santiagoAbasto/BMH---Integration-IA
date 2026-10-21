<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imagenes', function (Blueprint $table): void {
            $table->string('path_mobile')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('imagenes', function (Blueprint $table): void {
            $table->dropColumn('path_mobile');
        });
    }
};

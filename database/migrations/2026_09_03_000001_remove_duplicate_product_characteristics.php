<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicateProductCharacteristics();
        $this->removeDuplicateCategoryCharacteristics();

        if (Schema::hasTable('producto_caracteristica')) {
            Schema::table('producto_caracteristica', function (Blueprint $table): void {
                $table->unique(
                    ['producto_id', 'caracteristica_id'],
                    'producto_caracteristica_producto_caracteristica_unique',
                );
            });
        }

        if (Schema::hasTable('categoria_caracteristica')) {
            Schema::table('categoria_caracteristica', function (Blueprint $table): void {
                $table->unique(
                    ['categoria_id', 'caracteristica_id'],
                    'categoria_caracteristica_categoria_caracteristica_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('producto_caracteristica')) {
            Schema::table('producto_caracteristica', function (Blueprint $table): void {
                $table->dropUnique('producto_caracteristica_producto_caracteristica_unique');
            });
        }

        if (Schema::hasTable('categoria_caracteristica')) {
            Schema::table('categoria_caracteristica', function (Blueprint $table): void {
                $table->dropUnique('categoria_caracteristica_categoria_caracteristica_unique');
            });
        }
    }

    private function removeDuplicateProductCharacteristics(): void
    {
        if (!Schema::hasTable('producto_caracteristica')) {
            return;
        }

        $duplicateIds = DB::table('producto_caracteristica as older')
            ->join('producto_caracteristica as newer', function ($join): void {
                $join->on('newer.producto_id', '=', 'older.producto_id')
                    ->on('newer.caracteristica_id', '=', 'older.caracteristica_id')
                    ->whereColumn('newer.id', '>', 'older.id');
            })
            ->pluck('older.id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('producto_caracteristica')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function removeDuplicateCategoryCharacteristics(): void
    {
        if (!Schema::hasTable('categoria_caracteristica')) {
            return;
        }

        $duplicateIds = DB::table('categoria_caracteristica as older')
            ->join('categoria_caracteristica as newer', function ($join): void {
                $join->on('newer.categoria_id', '=', 'older.categoria_id')
                    ->on('newer.caracteristica_id', '=', 'older.caracteristica_id')
                    ->whereColumn('newer.id', '>', 'older.id');
            })
            ->pluck('older.id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('categoria_caracteristica')->whereIn('id', $duplicateIds)->delete();
        }
    }
};

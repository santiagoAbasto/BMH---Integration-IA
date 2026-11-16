<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «Trabajamos con»: los logos de las marcas que trabaja BMH (lista del cliente, 16/11/2026), en ese orden y en WebP
 * (public/img/marcas). Pars usa la versión nueva. Divaio, Prestolite y EP (los del Figma que no están en la lista)
 * quedan ocultos, no se borran. Faltan El Cidro, NF, Winly Tape y WST: no hay un logo confiable todavía; se suben
 * desde el admin cuando el cliente los pase. Si una marca ya existe (por nombre), se actualiza en vez de duplicarla.
 */
return new class extends Migration
{
    /** orden => [nombre, archivo] */
    private const MARCAS = [
        '01' => ['NTN', 'ntn'],
        '02' => ['ZM', 'zm'],
        '03' => ['Zen', 'zen'],
        '04' => ['Ron Bay', 'ron-bay'],
        '05' => ['SEG Automotive', 'seg'],
        '06' => ['3M', '3m'],
        '07' => ['Tamatel', 'tamatel'],
        '08' => ['AR-GON', 'ar-gon'],
        '09' => ['Eloc', 'eloc'],
        '11' => ['PNT Lubricantes', 'pnt'],
        '13' => ['Cygnus Electronics', 'cygnus'],
        '14' => ['DNI', 'dni'],
        '15' => ['Pars', 'pars'],
        '16' => ['Cremarras', 'cremarras'],
        '18' => ['Wasshardt', 'wasshardt'],
    ];

    private const OCULTAR = ['Divaio', 'Prestolite', 'EP'];

    public function up(): void
    {
        if (! Schema::hasTable('marcas_aliadas')) {
            return;
        }

        $ahora = now();
        foreach (self::MARCAS as $orden => [$nombre, $archivo]) {
            $datos = ['logo' => "img/marcas/{$archivo}.webp", 'orden' => (string) $orden, 'visible' => true, 'updated_at' => $ahora];
            $existente = DB::table('marcas_aliadas')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->value('id');
            if ($existente) {
                DB::table('marcas_aliadas')->where('id', $existente)->update($datos);
            } else {
                DB::table('marcas_aliadas')->insert($datos + ['nombre' => $nombre, 'created_at' => $ahora]);
            }
        }

        DB::table('marcas_aliadas')->whereIn('nombre', self::OCULTAR)->update(['visible' => false, 'updated_at' => $ahora]);
    }

    public function down(): void
    {
        // Sin vuelta atrás automática: los logos se administran desde Home › Trabajamos con.
    }
};

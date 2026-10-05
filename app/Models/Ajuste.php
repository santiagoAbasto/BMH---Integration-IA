<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Ajustes sueltos del sitio (clave → valor). Se leen en cada pedido del
 * catálogo, así que van cacheados. Si la tabla todavía no existe —falta correr
 * la migración— se devuelve el valor por defecto en vez de tirar el sitio.
 */
class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['clave', 'valor'];

    private const CACHE_PREFIJO = 'ajuste.';

    public static function obtener(string $clave, ?string $default = null): ?string
    {
        try {
            $valor = Cache::rememberForever(self::CACHE_PREFIJO.$clave, function () use ($clave) {
                return static::query()->whereKey($clave)->value('valor') ?? '';
            });
        } catch (QueryException) {
            return $default;
        }

        return $valor === '' ? $default : $valor;
    }

    public static function guardar(string $clave, string $valor): void
    {
        static::query()->updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        Cache::forget(self::CACHE_PREFIJO.$clave);
    }
}

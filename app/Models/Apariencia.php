<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Colores editables del header y el footer del sitio (Extras en el admin).
 *
 * Una sola fila. El layout del front la lee en cada request, por eso se
 * cachea para siempre y se invalida al guardar.
 */
class Apariencia extends Model
{
    protected $table = 'apariencia';

    /** Qué logo del header se usa en un estado dado. */
    public const LOGO_TRANSPARENTE = 'transparente';

    public const LOGO_BLANCO = 'blanco';

    public const LOGOS = [self::LOGO_TRANSPARENTE, self::LOGO_BLANCO];

    /**
     * Valores de fábrica: los colores que estaban fijos en styles2.css.
     * Deben coincidir con los defaults de la migración.
     */
    public const DEFAULTS = [
        'header_scroll_fondo' => '#0098DA',
        'header_scroll_links' => '#FFFFFF',
        'header_scroll_boton_texto' => '#FFFFFF',
        'header_scroll_boton_borde' => '#FFFFFF',
        'header_scroll_boton_hover_fondo' => '#0098DA',
        'header_scroll_boton_hover_texto' => '#FFFFFF',
        'header_scroll_logo' => self::LOGO_TRANSPARENTE,

        'header_mobile_fondo' => '#FFFFFF',
        'header_mobile_links' => '#000000',
        'header_mobile_boton_texto' => '#0098DA',
        'header_mobile_boton_borde' => '#0098DA',
        'header_mobile_boton_hover_fondo' => '#0098DA',
        'header_mobile_boton_hover_texto' => '#FFFFFF',
        'header_mobile_logo' => self::LOGO_BLANCO,

        'footer_fondo' => '#0098DA',
        'footer_texto' => '#FFFFFF',
        'footer_texto_hover' => '#201E1E',
    ];

    /** Campos de color del header, en el orden en que se editan. */
    public const CAMPOS_HEADER = [
        'header_scroll_fondo', 'header_scroll_links',
        'header_scroll_boton_texto', 'header_scroll_boton_borde',
        'header_scroll_boton_hover_fondo', 'header_scroll_boton_hover_texto',
        'header_mobile_fondo', 'header_mobile_links',
        'header_mobile_boton_texto', 'header_mobile_boton_borde',
        'header_mobile_boton_hover_fondo', 'header_mobile_boton_hover_texto',
    ];

    public const CAMPOS_FOOTER = ['footer_fondo', 'footer_texto', 'footer_texto_hover'];

    private const CACHE_KEY = 'apariencia.actual';

    protected $fillable = [
        'header_scroll_fondo', 'header_scroll_links',
        'header_scroll_boton_texto', 'header_scroll_boton_borde',
        'header_scroll_boton_hover_fondo', 'header_scroll_boton_hover_texto',
        'header_scroll_logo',
        'header_mobile_fondo', 'header_mobile_links',
        'header_mobile_boton_texto', 'header_mobile_boton_borde',
        'header_mobile_boton_hover_fondo', 'header_mobile_boton_hover_texto',
        'header_mobile_logo',
        'footer_fondo', 'footer_texto', 'footer_texto_hover',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * La fila vigente. Si la tabla todavía no existe —se subieron los archivos
     * pero falta correr la migración— devuelve los valores de fábrica en vez
     * de tirar abajo el sitio entero.
     */
    public static function actual(): self
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return static::query()->first() ?? static::query()->create(self::DEFAULTS);
            });
        } catch (QueryException) {
            return new static(self::DEFAULTS);
        }
    }

    /**
     * Variables CSS que consume el layout del front.
     *
     * @return array<string, string>
     */
    public function variablesCss(): array
    {
        $valor = fn (string $campo) => $this->{$campo} ?: self::DEFAULTS[$campo];

        return [
            '--ap-hs-fondo' => $valor('header_scroll_fondo'),
            '--ap-hs-links' => $valor('header_scroll_links'),
            '--ap-hs-btn-texto' => $valor('header_scroll_boton_texto'),
            '--ap-hs-btn-borde' => $valor('header_scroll_boton_borde'),
            '--ap-hs-btn-hover-fondo' => $valor('header_scroll_boton_hover_fondo'),
            '--ap-hs-btn-hover-texto' => $valor('header_scroll_boton_hover_texto'),
            '--ap-hm-fondo' => $valor('header_mobile_fondo'),
            '--ap-hm-links' => $valor('header_mobile_links'),
            '--ap-hm-btn-texto' => $valor('header_mobile_boton_texto'),
            '--ap-hm-btn-borde' => $valor('header_mobile_boton_borde'),
            '--ap-hm-btn-hover-fondo' => $valor('header_mobile_boton_hover_fondo'),
            '--ap-hm-btn-hover-texto' => $valor('header_mobile_boton_hover_texto'),
            '--ap-f-fondo' => $valor('footer_fondo'),
            '--ap-f-texto' => $valor('footer_texto'),
            '--ap-f-texto-hover' => $valor('footer_texto_hover'),
        ];
    }
}

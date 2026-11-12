<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Metadatos;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Páginas generales del sitio con sus metadatos sugeridos (como en Conelec). En admin → Metadatos SEO se pueden
 * reemplazar; un campo vacío vuelve al sugerido. Lo propio se guarda en `metadatos` (una fila por sección, las
 * mismas claves que ya existían). Las fichas (productos, categorías y novedades) se arman solas: ver DynamicSeo.
 */
final class SeoPages
{
    private const CACHE_KEY = 'seo.pages';

    /** @return array<string, array{label: string, route: string, title: string, description: string, keywords: list<string>}> */
    public static function definitions(): array
    {
        return [
            'home' => [
                'label' => 'Inicio', 'route' => 'home',
                'title' => 'BMH Distribuidora · La marca del inducido',
                'description' => 'Rotores, estatores, inducidos y más de 5.200 repuestos eléctricos para el automotor. Calidad de fábrica y entregas en todo el país desde San Justo.',
                'keywords' => ['repuestos eléctricos automotor', 'inducidos', 'rotores', 'estatores', 'alternadores', 'motores de arranque', 'distribuidora de repuestos', 'BMH'],
            ],
            'nosotros' => [
                'label' => 'Nosotros', 'route' => 'nosotros',
                'title' => 'Nosotros · Bobinajes San Francisco desde 1966 | BMH',
                'description' => 'Empresa familiar fundada en 1966: fabricamos rotores, estatores e inducidos en nuestra planta de San Justo y distribuimos repuestos a todo el país.',
                'keywords' => ['Bobinajes San Francisco', 'fábrica de inducidos', 'fábrica de rotores y estatores', 'San Justo', 'BMH Distribuidora'],
            ],
            'productos' => [
                'label' => 'Productos', 'route' => 'categorias',
                'title' => 'Productos · Repuestos eléctricos para el automotor | BMH',
                'description' => 'Líneas de motores de arranque, alternadores, rodamientos, reguladores, inducidos, rotores, estatores y más. Buscá por código, marca o vehículo.',
                'keywords' => ['repuestos de alternador', 'repuestos de motor de arranque', 'reguladores de voltaje', 'rodamientos', 'inducidos', 'escobillas', 'catálogo BMH'],
            ],
            'ofertas' => [
                'label' => 'Ofertas', 'route' => 'ofertas',
                'title' => 'Ofertas en repuestos eléctricos | BMH',
                'description' => 'Repuestos eléctricos para el automotor en oferta: alternadores, arranques, rodamientos y más, con la calidad de siempre.',
                'keywords' => ['ofertas repuestos eléctricos', 'repuestos automotor en oferta', 'BMH ofertas'],
            ],
            'novedades' => [
                'label' => 'Novedades', 'route' => 'novedades',
                'title' => 'Novedades · Productos nuevos y noticias | BMH',
                'description' => 'Lanzamientos, productos nuevos y noticias de BMH Distribuidora.',
                'keywords' => ['novedades BMH', 'repuestos nuevos', 'lanzamientos repuestos eléctricos'],
            ],
            'contacto' => [
                'label' => 'Contacto', 'route' => 'contacto',
                'title' => 'Contacto · BMH Distribuidora, San Justo',
                'description' => 'Escribinos o visitanos en Constantino Gaito 2874, San Justo, Buenos Aires. Lunes a viernes de 8 a 17 hs.',
                'keywords' => ['contacto BMH', 'BMH San Justo', 'distribuidora de repuestos eléctricos Buenos Aires'],
            ],
        ];
    }

    public static function keyForRoute(?string $route): ?string
    {
        foreach (self::definitions() as $key => $page) {
            if ($page['route'] === $route) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Valores efectivos de cada página: el propio si se cargó, si no el sugerido.
     *
     * @return array<string, array{key: string, label: string, route: string, title: string, description: string, keywords: list<string>, defaults: array{title: string, description: string, keywords: list<string>}, custom: bool}>
     */
    public static function all(): array
    {
        $saved = Schema::hasTable('metadatos') && Schema::hasColumn('metadatos', 'titulo')
            ? Cache::rememberForever(self::CACHE_KEY, fn (): array => Metadatos::query()->get()->keyBy('seccion')
                ->map(fn (Metadatos $m): array => ['titulo' => $m->titulo, 'descripcion' => $m->descripcion, 'palabras_clave' => $m->keyword])->all())
            : [];

        $pages = [];
        foreach (self::definitions() as $key => $page) {
            $own = array_map(fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null, $saved[$key] ?? []);
            $keywords = SeoText::split($own['palabras_clave'] ?? null);
            $pages[$key] = [
                'key' => $key,
                'label' => $page['label'],
                'route' => $page['route'],
                'title' => ($own['titulo'] ?? null) ?: $page['title'],
                'description' => ($own['descripcion'] ?? null) ?: $page['description'],
                'keywords' => $keywords ?: $page['keywords'],
                'defaults' => ['title' => $page['title'], 'description' => $page['description'], 'keywords' => $page['keywords']],
                'custom' => array_filter($own) !== [],
            ];
        }

        return $pages;
    }

    /** @return array{key: string, label: string, route: string, title: string, description: string, keywords: list<string>, defaults: array{title: string, description: string, keywords: list<string>}, custom: bool}|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Verificación de buscadores e imagen para compartir. Se cachea como array (no como modelo).
     *
     * @return array{google: ?string, bing: ?string, image: string}
     */
    public static function settings(): array
    {
        if (! Schema::hasTable('seo_settings')) {
            return ['google' => null, 'bing' => null, 'image' => asset(SeoSetting::DEFAULT_IMAGE)];
        }

        $settings = Cache::rememberForever(self::CACHE_KEY.'.settings', fn (): array => SeoSetting::current()->only(['google_verificacion', 'bing_verificacion', 'imagen_path']));

        // La URL se arma en cada pedido (no se cachea con el dominio del primero que la pidió).
        return [
            'google' => $settings['google_verificacion'],
            'bing' => $settings['bing_verificacion'],
            'image' => (new SeoSetting(['imagen_path' => $settings['imagen_path']]))->imageUrl(),
        ];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.settings');
    }
}

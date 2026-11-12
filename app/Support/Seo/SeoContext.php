<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Contacto;
use App\Services\LogosSitio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Metadatos de la página que se está mostrando (uno por request). Las fichas cargan los suyos con set(); las
 * páginas generales los toman de SeoPages por nombre de ruta; el resto usa el nombre del sitio.
 */
final class SeoContext
{
    private const SITE = 'BMH Distribuidora';

    private ?SeoMeta $meta = null;

    private ?string $robots = null;

    public function set(SeoMeta $meta): void
    {
        $this->meta = $meta;
    }

    /** Para páginas que no deben indexarse (Zona de Clientes, búsquedas sin resultados…). */
    public function noindex(bool $follow = true): void
    {
        $this->robots = $follow ? 'noindex, follow' : 'noindex, nofollow';
    }

    public function resolve(Request $request): SeoMeta
    {
        $route = $request->route()?->getName();
        $page = $this->meta === null ? SeoPages::find((string) SeoPages::keyForRoute($route)) : null;

        $meta = $this->meta ?? ($page !== null
            ? new SeoMeta(
                title: $page['title'],
                description: $page['description'],
                keywords: $page['keywords'],
                canonical: $request->url(),
                schema: $route === 'home' ? $this->organizationSchema() : [],
            )
            : new SeoMeta(title: self::SITE, canonical: $request->url()));

        // Listados filtrados, búsquedas o páginas 2+: se siguen los links pero no se indexan.
        $filtros = array_diff(array_keys($request->query()), ['categoria']);
        if ($this->robots === null && in_array($route, ['productos', 'search', 'ofertas'], true) && $filtros !== []) {
            $this->robots = 'noindex, follow';
        }
        if ($this->robots !== null) {
            $meta = $meta->with(['robots' => $this->robots]);
        }

        // Sin imagen propia (páginas generales, productos sin foto), se comparte la imagen del sitio.
        return $meta->image ? $meta : $meta->with(['image' => SeoPages::settings()['image'], 'imageAlt' => 'BMH Distribuidora · La marca del inducido']);
    }

    /** @return array{google: ?string, bing: ?string} */
    public function verification(): array
    {
        $settings = SeoPages::settings();

        return ['google' => $settings['google'], 'bing' => $settings['bing']];
    }

    /** @return list<array<string, mixed>> Empresa y sitio web, para el panel de Google de la marca. */
    private function organizationSchema(): array
    {
        $contacto = Schema::hasTable('contacto') ? Contacto::query()->find(1) : null;
        // Sólo perfiles reales: "https://www.facebook.com/" sin la página de la empresa no identifica a nadie.
        $social = collect([$contacto?->instagram, $contacto?->facebook, $contacto?->tiktok])
            ->filter(fn (?string $url): bool => is_string($url) && str_starts_with($url, 'http') && trim((string) parse_url($url, PHP_URL_PATH), '/') !== '')
            ->values()->all();

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => self::SITE,
                'legalName' => 'B.M.H Bobinajes San Francisco S.R.L.',
                'foundingDate' => '1966',
                'slogan' => 'La marca del inducido',
                'url' => route('home'),
                'logo' => url(app(LogosSitio::class)->url(LogosSitio::HEADER_TRANSPARENTE)),
                'email' => $contacto?->mail ?: null,
                'telephone' => $contacto?->tel ?: null,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Constantino Gaito 2874',
                    'postalCode' => '1754',
                    'addressLocality' => 'San Justo',
                    'addressRegion' => 'Buenos Aires',
                    'addressCountry' => 'AR',
                ],
                'sameAs' => $social ?: null,
            ]),
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => self::SITE,
                'url' => route('home'),
                'inLanguage' => 'es-AR',
            ],
        ];
    }
}

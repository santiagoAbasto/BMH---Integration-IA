{{-- Metadatos del <head> (App\Support\Seo\SeoContext): título, descripción, palabras clave, canónica, Open Graph,
     X/Twitter, verificación de buscadores y datos estructurados. Se editan en admin → Metadatos SEO. --}}
@php
    $seoContexto = app(\App\Support\Seo\SeoContext::class);
    // Zona de Clientes: páginas privadas, no se indexan.
    if (! empty($zonaclientes)) {
        $seoContexto->noindex(false);
    }
    $seo = $seoContexto->resolve(request());
    $verificacion = $seoContexto->verification();
@endphp
<title>{{ $seo->title }}</title>
@if ($seo->description)<meta name="description" content="{{ $seo->description }}">@endif
@if ($seo->keywords !== [])<meta name="keywords" content="{{ implode(', ', $seo->keywords) }}">@endif
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->canonical)<link rel="canonical" href="{{ $seo->canonical }}">@endif
<meta property="og:site_name" content="BMH Distribuidora">
<meta property="og:locale" content="es_AR">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->title }}">
@if ($seo->description)<meta property="og:description" content="{{ $seo->description }}">@endif
@if ($seo->canonical)<meta property="og:url" content="{{ $seo->canonical }}">@endif
@if ($seo->image)
<meta property="og:image" content="{{ $seo->image }}">
@if ($seo->imageAlt)<meta property="og:image:alt" content="{{ $seo->imageAlt }}">@endif
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->title }}">
@if ($seo->description)<meta name="twitter:description" content="{{ $seo->description }}">@endif
@if ($seo->image)<meta name="twitter:image" content="{{ $seo->image }}">@endif
@if ($verificacion['google'])<meta name="google-site-verification" content="{{ $verificacion['google'] }}">@endif
@if ($verificacion['bing'])<meta name="msvalidate.01" content="{{ $verificacion['bing'] }}">@endif
@foreach ($seo->schema as $bloque)
<script type="application/ld+json">{!! json_encode($bloque, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach

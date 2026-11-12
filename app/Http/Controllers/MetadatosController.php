<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Metadatos;
use App\Models\Novedad;
use App\Models\Producto;
use App\Models\SeoSetting;
use App\Services\ImagenCompartir;
use App\Support\Seo\DynamicSeo;
use App\Support\Seo\SeoPages;
use App\Support\Seo\SeoText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Admin → Metadatos SEO (como en Conelec): páginas generales con valores sugeridos, ejemplos de las fichas que se
 * arman solas, imagen para compartir y verificación de Google Search Console y Bing Webmaster Tools.
 * También sirve el sitemap.xml.
 */
final class MetadatosController extends Controller
{
    public function index(): View
    {
        $producto = Producto::query()->with(['categoria', 'portadaImagen'])
            ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
            ->whereHas('portadaImagen')->orderBy('id')->first();
        $categoria = Categoria::query()->orderBy('orden')->orderBy('nombre')->first();
        $novedad = Novedad::query()->orderBy('orden')->first();

        return view('backend/dash-metadatos', [
            'paginas' => SeoPages::all(),
            'ajustes' => SeoSetting::current(),
            'ejemplos' => array_filter([
                'Ficha de producto' => $producto ? ['meta' => DynamicSeo::producto($producto), 'datos' => 'Código, nombre, categoría, marca, estado, descripción y la foto principal'] : null,
                'Listado de una categoría' => $categoria ? ['meta' => DynamicSeo::categoria($categoria), 'datos' => 'Nombre de la categoría'] : null,
                'Novedad' => $novedad ? ['meta' => DynamicSeo::novedad($novedad), 'datos' => 'Título, epígrafe y la foto de portada'] : null,
            ]),
        ]);
    }

    public function updatePagina(Request $request, string $pagina): RedirectResponse
    {
        $definicion = SeoPages::definitions()[$pagina] ?? abort(404);
        $datos = $request->validate([
            'titulo' => ['nullable', 'string', 'max:70'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'palabras_clave' => ['nullable', 'string', 'max:255'],
        ], [
            'titulo.max' => 'El título admite hasta 70 caracteres; Google muestra unos 60.',
            'descripcion.max' => 'La descripción admite hasta 200 caracteres; Google muestra unos 160.',
            'palabras_clave.max' => 'Las palabras clave admiten hasta 255 caracteres en total.',
        ]);
        $palabras = SeoText::split($datos['palabras_clave'] ?? null);
        $texto = fn (?string $v): ?string => is_string($v) && trim($v) !== '' ? trim($v) : null;

        // Lo que coincide con lo sugerido no se guarda: así sigue las mejoras futuras de los valores sugeridos.
        $titulo = $texto($datos['titulo'] ?? null);
        $descripcion = $texto($datos['descripcion'] ?? null);
        $valores = [
            'titulo' => $titulo !== $definicion['title'] ? $titulo : null,
            'descripcion' => $descripcion !== $definicion['description'] ? $descripcion : null,
            'keyword' => $palabras !== [] && $palabras !== $definicion['keywords'] ? implode(', ', $palabras) : null,
        ];

        // La fila de la sección se conserva (ya existían) y queda vacía si vuelve a lo sugerido.
        Metadatos::query()->updateOrCreate(['seccion' => $pagina], $valores);

        $guardada = SeoPages::find($pagina);

        return back()->with('success', $guardada['custom']
            ? "Metadatos de {$guardada['label']} guardados."
            : "{$guardada['label']} usa los metadatos sugeridos.")->withFragment('pagina-'.$pagina);
    }

    public function updateVerificacion(Request $request): RedirectResponse
    {
        // Se acepta el código solo o la etiqueta completa que dan Google y Bing: se guarda el valor de content.
        $request->merge(collect(['google_verificacion', 'bing_verificacion'])->mapWithKeys(function (string $clave) use ($request): array {
            $valor = trim((string) $request->input($clave));
            if (preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $valor, $m)) {
                $valor = trim($m[1]);
            }

            return [$clave => $valor === '' ? null : $valor];
        })->all());

        $regla = ['nullable', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_\-=.]+\z/'];
        $datos = $request->validate(
            ['google_verificacion' => $regla, 'bing_verificacion' => $regla],
            ['regex' => 'Pegá el código de verificación o la etiqueta <meta> completa que te da el buscador.', 'max' => 'El código es demasiado largo.'],
        );
        SeoSetting::current()->update($datos);

        return back()->with('success', 'Verificación de buscadores guardada.')->withFragment('verificacion');
    }

    public function updateImagen(Request $request, ImagenCompartir $imagenes): RedirectResponse
    {
        $request->validate(
            ['imagen' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=600,min_height=315']],
            [
                'imagen.required' => 'Elegí una imagen.',
                'imagen.mimes' => 'Usá una imagen JPG, PNG o WebP.',
                'imagen.max' => 'La imagen puede pesar hasta 8 MB.',
                'imagen.dimensions' => 'La imagen tiene que medir al menos 600 × 315 px (ideal 1200 × 630).',
            ],
        );
        $ajustes = SeoSetting::current();
        $anterior = $ajustes->imagen_path;
        $ajustes->update(['imagen_path' => $imagenes->guardar($request->file('imagen'))]);
        $imagenes->borrar($anterior);

        return back()->with('success', 'Imagen para compartir actualizada.')->withFragment('imagen');
    }

    public function destroyImagen(ImagenCompartir $imagenes): RedirectResponse
    {
        $ajustes = SeoSetting::current();
        $imagenes->borrar($ajustes->imagen_path);
        $ajustes->update(['imagen_path' => null]);

        return back()->with('success', 'Se volvió a la imagen original del sitio.')->withFragment('imagen');
    }

    /** sitemap.xml: páginas generales, categorías, productos publicados y novedades. */
    public function sitemap(): Response
    {
        $urls = collect(SeoPages::definitions())
            ->map(fn (array $p): array => ['loc' => route($p['route']), 'lastmod' => null])->values()
            ->merge(Categoria::query()->orderBy('id')->get(['id', 'updated_at'])->map(fn (Categoria $c): array => ['loc' => route('productos', ['categoria' => $c->id]), 'lastmod' => $c->updated_at]))
            ->merge(Producto::query()->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))->orderBy('id')->get(['id', 'updated_at'])
                ->map(fn (Producto $p): array => ['loc' => route('producto', ['id' => $p->id]), 'lastmod' => $p->updated_at]))
            ->merge(Novedad::query()->orderBy('orden')->get(['id', 'updated_at'])->map(fn (Novedad $n): array => ['loc' => route('novedad', ['id' => $n->id]), 'lastmod' => $n->updated_at]));

        return response()->view('sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

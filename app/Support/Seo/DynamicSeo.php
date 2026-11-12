<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Categoria;
use App\Models\Novedad;
use App\Models\Producto;

/**
 * Metadatos que se arman solos con los datos de cada ficha (como en Conelec): no hay que cargarlos a mano y
 * se actualizan cuando cambia el producto, la categoría o la novedad.
 */
final class DynamicSeo
{
    public static function producto(Producto $producto): SeoMeta
    {
        $nombre = SeoText::plain($producto->nombre);
        $categoria = $producto->categoria?->nombre ? self::titulo($producto->categoria->nombre) : null;
        $codigo = trim((string) $producto->codigo);
        $marca = trim((string) $producto->marca);
        $estado = match ((int) $producto->estado) {
            2 => 'reconstruido',
            1 => 'nuevo',
            default => null,
        };

        $descripcion = SeoText::plain($producto->descripcion);
        $resumen = implode(' ', array_filter([
            $codigo !== '' ? "Código {$codigo}." : null,
            $categoria ? "{$categoria}" . ($marca !== '' ? " {$marca}" : '') . ($estado ? ", {$estado}." : '.') : null,
            $descripcion !== '' ? $descripcion : 'Consultá precio y disponibilidad en BMH Distribuidora.',
        ]));

        $portada = $producto->portadaImagen?->path;

        return new SeoMeta(
            title: SeoText::title(trim(($codigo !== '' ? $codigo.' · ' : '').$nombre), $categoria),
            description: SeoText::limit($resumen, SeoText::DESCRIPTION_LIMIT),
            keywords: SeoText::keywords([$codigo, $nombre, $categoria, $marca, $categoria && $marca !== '' ? "{$categoria} {$marca}" : null, 'BMH']),
            canonical: route('producto', ['id' => $producto->id]),
            image: $portada && is_file(public_path('imagenes/'.$portada)) ? asset('imagenes/'.$portada) : null,
            imageAlt: $nombre,
            type: 'product',
        );
    }

    public static function categoria(Categoria $categoria): SeoMeta
    {
        $nombre = self::titulo($categoria->nombre);

        return new SeoMeta(
            title: SeoText::title($nombre, 'Repuestos eléctricos'),
            description: SeoText::limit("{$nombre} para el automotor en BMH Distribuidora: buscá por código, marca, vehículo o medida y consultá disponibilidad.", SeoText::DESCRIPTION_LIMIT),
            keywords: SeoText::keywords([$nombre, "{$nombre} automotor", 'repuestos eléctricos', 'BMH']),
            canonical: route('productos', ['categoria' => $categoria->id]),
        );
    }

    public static function novedad(Novedad $novedad): SeoMeta
    {
        $titulo = SeoText::plain($novedad->titulo);
        $resumen = SeoText::plain($novedad->epigrafe) ?: SeoText::plain($novedad->texto);

        return new SeoMeta(
            title: SeoText::title($titulo, 'Novedades'),
            description: $resumen !== '' ? SeoText::limit($resumen, SeoText::DESCRIPTION_LIMIT) : null,
            keywords: SeoText::keywords([$titulo, SeoText::plain($novedad->etiqueta), 'novedades BMH']),
            canonical: route('novedad', ['id' => $novedad->id]),
            image: $novedad->portada && is_file(public_path('imagenes/'.$novedad->portada)) ? asset('imagenes/'.$novedad->portada) : null,
            imageAlt: $titulo,
            type: 'article',
        );
    }

    /** "MOTORES DE ARRANQUE" → "Motores de arranque" (las categorías se cargan en mayúsculas). */
    private static function titulo(?string $texto): string
    {
        $texto = SeoText::plain($texto);

        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_strtolower(mb_substr($texto, 1));
    }
}

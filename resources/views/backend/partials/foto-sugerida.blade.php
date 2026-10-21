{{-- Tamaño sugerido para las fotos de producto (config imagenes.tamano_sugerido). --}}
@php($lado = (int) config('imagenes.tamano_sugerido.lado', 1200))
<span class="recomendada" title="Las cards muestran la foto completa en un cuadrado: cuadrada se ve sin márgenes. Mínimo {{ (int) config('imagenes.tamano_sugerido.minimo', 800) }} px de lado para que no se vea borrosa.">(cuadrada · ideal {{ $lado }} × {{ $lado }} px)</span>

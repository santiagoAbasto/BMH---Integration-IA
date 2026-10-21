{{-- Achica en el navegador las fotos de los <input type="file" data-achicar> antes de subirlas y avisa si no son cuadradas o son chicas. --}}
<script src="{{ asset('js/achicar-imagenes.js') }}?v=3"
        data-lado-maximo="{{ (int) config('imagenes.lado_maximo', 2000) }}"
        data-lado-sugerido="{{ (int) config('imagenes.tamano_sugerido.lado', 1200) }}"
        data-lado-minimo="{{ (int) config('imagenes.tamano_sugerido.minimo', 800) }}"></script>
<style>
    .achicar-sugerencia { margin-top: 6px; padding: 6px 10px; border: 1px solid #F5D98B; border-radius: 6px; background: #FFF8E6; color: #7A5A00; font-size: 12.5px; line-height: 1.4; }
    .achicar-sugerencia[hidden] { display: none; }
</style>

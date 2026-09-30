{{-- Achica en el navegador las fotos de los <input type="file" data-achicar> antes de subirlas. --}}
<script src="{{ asset('js/achicar-imagenes.js') }}?v=1" data-lado-maximo="{{ (int) config('imagenes.lado_maximo', 2000) }}"></script>

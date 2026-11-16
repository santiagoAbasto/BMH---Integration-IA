{{-- Animaciones del sitio público (App\Support\Motion + resources/js/components/motion.js + css/sitio/motion.css),
     como en Conelec y Rapivac. Va al principio del <head> para que el estado oculto exista antes de pintar. Se activan
     con html.motion, que no se pone si se pidió reducir el movimiento: así todo se ve quieto y desde el principio. Si
     el JS no llega a correr, a los 2,5 s se muestra todo igual. Con Turbo este script y el estilo no se repiten. --}}
<script>
    (function () {
        var root = document.documentElement;
        if (root.classList.contains('motion') || !window.matchMedia || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        root.classList.add('motion');
        setTimeout(function () { if (!root.classList.contains('motion-ready')) root.classList.remove('motion'); }, 2500);
    })();
</script>
<style>{!! \App\Support\Motion::hiddenCss() !!}</style>
<script type="application/json" id="motion-targets">@json(\App\Support\Motion::targets())</script>

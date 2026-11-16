{{--
    Una vista previa de Extras: el header o el footer reales (extras.vista-previa) dibujados a su ancho de verdad y
    achicados al lugar que hay (backend/extras/_script). Se cargan cuando se ven (loading=lazy) y no toman el foco.

    @param string $pieza   header | footer
    @param string $estado  ver AparienciaController::ESTADOS_HEADER
    @param int    $ancho   ancho de la ventana simulada (px)
    @param int    $alto    alto visible (px); el footer se ajusta solo a su alto
    @param string $titulo
--}}
<div class="ext-iframe" style="--vp-ancho: {{ $ancho }}; --vp-alto: {{ $alto }}">
  <iframe src="{{ route('extras.vista-previa', ['pieza' => $pieza, 'estado' => $pieza === 'header' ? $estado : null]) }}"
          width="{{ $ancho }}" height="{{ $alto }}" title="{{ $titulo }}" loading="lazy" tabindex="-1"
          data-vista-previa @if ($pieza === 'footer') data-alto-auto @endif></iframe>
</div>

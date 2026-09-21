{{--
    Barra de contacto de arriba del header: teléfono, mail y redes.

    Igual en todas las páginas. En celular no se muestra (styles2.css) y en
    computadora se esconde al hacer scroll (script de plantilla-front).

    Los datos salen de `contacto`, los mismos del footer y la página Contacto;
    los colores, de admin → Extras → Barra superior (layouts/partials/apariencia).
--}}
@php
    $contacto ??= \App\Models\Contacto::actual();
    $telHref = $contacto->telHref();
@endphp
<div class="infoHeader" id="site-topbar">
  <div class="container-fluid barra-superior" style="height: 31px;">
    <div class="container">
      <div class="row justify-content-beetween">

        <div class="col-lg-6 pt-1 d-flex">
          <div>
            @if ($telHref)
              <a href="{{ $telHref }}" aria-label="Llamar al {{ $contacto->tel }}">
                @include('layouts.partials.iconos-barra', ['icono' => 'tel'])
                <span class="headerT">{{ $contacto->tel }} </span>
              </a>
            @endif
          </div>

          <div class="emailH">
            @if (filled($contacto->mail))
              <a class="d-flex mailM" href="mailto:{{ $contacto->mail }}">
                @include('layouts.partials.iconos-barra', ['icono' => 'mail'])
                <span class="headerT mailMs">{{ $contacto->mail }} </span>
              </a>
            @endif
          </div>
        </div>

        <div class="col-lg-6 d-flex justify-content-end presupuestoM">
          @foreach ($contacto->redesBarra() as $red => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ \App\Models\Contacto::REDES_BARRA[$red] }} de BMH">
              <div>@include('layouts.partials.iconos-barra', ['icono' => $red])</div>
            </a>
          @endforeach
        </div>

      </div>
    </div>
  </div>
</div>

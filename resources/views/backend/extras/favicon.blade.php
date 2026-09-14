@extends('layouts.plantilla-back')

@section('styles')
  @include('backend.extras._estilos')
@endsection

@section('content')
<div class="ext" data-ext data-logos='@json(['favicon' => $faviconUrl])' data-errores="{{ $errors->any() ? 1 : 0 }}">
  <div class="ext-encabezado">
    <div>
      <h1>Favicon</h1>
      <p>El icono que se muestra en la pestaña del navegador. Al guardarlo se actualiza en todo el sitio, incluido este panel.</p>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}. Ya se ve así en el sitio.</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">No se guardó: revisá el archivo marcado.</div>
  @endif

  <form id="ext-form" method="POST" action="{{ route('extras.favicon.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <section class="ext-card ext-favicon-card">
      <h2>Icono del sitio</h2>
      <p class="ext-ayuda">Proporción recomendada: <strong>1:1</strong>. Para una mejor definición, usá un PNG cuadrado de <strong>512 x 512 px</strong>.</p>
      <div class="ext-logo ext-favicon" data-parte="favicon">
        <div class="ext-logo__escenario ext-favicon__escenario">
          <img src="{{ $faviconUrl }}" alt="Favicon actual" data-logo-estado="favicon">
        </div>
        <div class="ext-logo__cuerpo">
          <p class="ext-logo__titulo">Favicon actual</p>
          <div class="ext-logo__acciones">
            <input type="file" id="favicon" name="favicon" class="ext-visually-hidden" accept=".png,.jpg,.jpeg,.webp,.svg,.ico" data-logo-input="favicon" required>
            <label class="btn btn-sm btn-outline-primary mb-0" for="favicon">
              <i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Cambiar favicon
            </label>
            <span class="ext-archivo" data-archivo="favicon">PNG, JPG, WEBP, SVG o ICO · hasta 2 MB</span>
          </div>
          @error('favicon') <p class="ext-color__error">{{ $message }}</p> @enderror
        </div>
      </div>
    </section>

    <div class="ext-guardar">
      <span class="ext-guardar__estado" role="status">Sin cambios</span>
      <button type="button" class="btn btn-outline-secondary" data-descartar>Descartar</button>
      <button type="submit" class="btn btn-primary" data-guardar><i class="fa-solid fa-check" aria-hidden="true"></i> Guardar cambios</button>
    </div>
  </form>
</div>
@endsection

@section('script')
  @include('backend.extras._script')
@endsection

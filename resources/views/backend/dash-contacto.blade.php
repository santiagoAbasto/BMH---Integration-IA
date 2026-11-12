@extends('layouts.plantilla-back')

{{-- Admin → Contacto › Contenido: los datos que muestra el sitio (barra superior, footer y Contacto) y a quién
     le llegan las consultas del formulario. Guarda ContactoController@update. La bandeja: Contacto › Mensajes. --}}

@section('content')

<x-osole::admin-page-header.v1 eyebrow="Contacto" title="Contenido" description="Los datos de contacto que se muestran en el sitio y a qué email llegan las consultas del formulario.">
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard.contacto.mensajes') }}">
            <i class="fa-regular fa-envelope" aria-hidden="true"></i> Ver mensajes
            @if ($sinLeer)<span class="adm-chip adm-chip--marca ms-1">{{ $sinLeer }} sin leer</span>@endif
        </a>
    </x-slot:actions>
</x-osole::admin-page-header.v1>

@if (session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Revisá los datos marcados.</strong>
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

@php
    $valor = fn (string $campo) => old($campo, $contacto->{$campo});
    $destinatarios = $contacto->destinatariosAviso();
    $usaRespaldo = blank($contacto->emails_aviso);
@endphp

<form action="{{ route('contacto.update') }}" method="POST" novalidate>
    @csrf
    @method('PUT')

    <div class="adm-pila">

        {{-- Avisos del formulario --}}
        <div class="card" id="avisos">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2>Avisos del formulario</h2>
                    <p>Cada consulta se guarda en <a href="{{ route('dashboard.contacto.mensajes') }}">Mensajes</a> y además llega por email.</p>
                </div>
                @if ($destinatarios)
                    <span class="adm-chip adm-chip--ok">Llegan a {{ count($destinatarios) === 1 ? '1 email' : count($destinatarios).' emails' }}</span>
                @else
                    <span class="adm-chip adm-chip--alerta">Sin email de avisos</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="emails_aviso" class="form-label">¿A qué mail querés que lleguen los mensajes?</label>
                        <textarea id="emails_aviso" name="emails_aviso" rows="2" maxlength="500" aria-describedby="emails_aviso-ayuda"
                            @class(['form-control', 'is-invalid' => $errors->has('emails_aviso')])
                            placeholder="ventas@bmh.com.ar, consultas@bmh.com.ar">{{ $valor('emails_aviso') }}</textarea>
                        @error('emails_aviso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="contacto-avisos__ayuda" id="emails_aviso-ayuda">
                            Uno o varios, separados por comas (hasta 10).
                            @if ($usaRespaldo)
                                @if (filled($contacto->mail))
                                    Mientras esté vacío, llegan al email de contacto: <strong>{{ $contacto->mail }}</strong>.
                                @else
                                    Vacío y sin email de contacto, las consultas sólo quedan en Mensajes.
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="col-md-8">
                        <label for="asunto_aviso" class="form-label">Asunto del aviso</label>
                        <input id="asunto_aviso" type="text" name="asunto_aviso" maxlength="150" value="{{ $valor('asunto_aviso') }}"
                            @class(['form-control', 'is-invalid' => $errors->has('asunto_aviso')]) placeholder="{{ \App\Models\Contacto::ASUNTO_AVISO }}" aria-describedby="asunto_aviso-ayuda">
                        @error('asunto_aviso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="contacto-avisos__ayuda" id="asunto_aviso-ayuda">Se le suma el motivo y el nombre: «{{ $contacto->asuntoAviso() }}: Consulta técnica · Juan Pérez».</p>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <input type="hidden" name="acuse_recibo" value="0">
                        <label class="adm-switch">
                            <input type="checkbox" role="switch" name="acuse_recibo" value="1" @checked(old('acuse_recibo', $contacto->mandaAcuse() ? '1' : '0') === '1')>
                            Mandar acuse de recibo a quien escribe
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos de contacto --}}
        <div class="card">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2>Datos de contacto</h2>
                    <p>Se muestran en la barra superior, el footer y la página de Contacto.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input id="direccion" type="text" name="direccion" maxlength="255" value="{{ $valor('direccion') }}" @class(['form-control', 'is-invalid' => $errors->has('direccion')]) autocomplete="street-address">
                        @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="mail" class="form-label">Email de contacto</label>
                        <input id="mail" type="email" name="mail" maxlength="255" value="{{ $valor('mail') }}" @class(['form-control', 'is-invalid' => $errors->has('mail')]) autocomplete="email">
                        @error('mail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="tel" class="form-label">Teléfono</label>
                        <input id="tel" type="text" name="tel" maxlength="255" value="{{ $valor('tel') }}" @class(['form-control', 'is-invalid' => $errors->has('tel')]) autocomplete="tel">
                        @error('tel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input id="whatsapp" type="text" name="whatsapp" maxlength="255" value="{{ $valor('whatsapp') }}" @class(['form-control', 'is-invalid' => $errors->has('whatsapp')]) placeholder="+54 9 11 1234-5678">
                        @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="iframe" class="form-label">Mapa (iframe de Google Maps)</label>
                        <textarea id="iframe" name="iframe" rows="3" @class(['form-control', 'font-monospace', 'is-invalid' => $errors->has('iframe')]) aria-describedby="iframe-ayuda">{{ $valor('iframe') }}</textarea>
                        @error('iframe')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="contacto-avisos__ayuda" id="iframe-ayuda">En Google Maps: Compartir › Insertar un mapa › Copiar HTML. Vacío, la página de Contacto no muestra el mapa.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Redes --}}
        <div class="card">
            <div class="card-header">
                <div class="adm-card-titulo">
                    <h2>Redes sociales</h2>
                    <p>El link completo de cada perfil. Una red vacía no se muestra en el sitio.</p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $campo => $red)
                        <div class="col-md-4">
                            <label for="{{ $campo }}" class="form-label">{{ $red }}</label>
                            <input id="{{ $campo }}" type="url" name="{{ $campo }}" maxlength="255" value="{{ $valor($campo) }}" @class(['form-control', 'is-invalid' => $errors->has($campo)]) placeholder="https://">
                            @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </div>
</form>

@endsection

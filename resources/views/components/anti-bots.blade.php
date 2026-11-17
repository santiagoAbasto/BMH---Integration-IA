@props(['id' => null])
{{-- Campos del filtro anti-bots (App\Http\Middleware\EnsureHumanFormSubmission), dentro de cada <form> público:
     el campo trampa (fuera de pantalla, sin foco ni autocompletar, oculto para lectores de pantalla) y el token
     firmado con la hora en que se mostró el formulario. «id»: prefijo único si hay dos formularios en la página. --}}
@php($campo = ($id ?? 'form-'.\Illuminate\Support\Str::random(6)).'-website')
<div aria-hidden="true" style="position:absolute!important;left:-10000px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;">
    <label for="{{ $campo }}">No completes este campo</label>
    <input id="{{ $campo }}" name="{{ \App\Http\Middleware\EnsureHumanFormSubmission::HONEYPOT }}" type="text" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Http\Middleware\EnsureHumanFormSubmission::TOKEN }}" value="{{ \App\Http\Middleware\EnsureHumanFormSubmission::token() }}" data-form-token>

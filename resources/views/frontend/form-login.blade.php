{{--
    Ingreso a la Zona de Clientes (Figma: «INGRESO»), dentro del popover #bmh-ingreso del header.

    Lo envía por AJAX el script de layouts/plantilla-front (form.ingreso → user.login): los id
    (#input_type, #password, #toggle-password, #nosotros-mas) y los .username-error /
    .password-error son los que usa ese script.
--}}
<h2 class="bmh-ingreso__titulo">Iniciar sesión</h2>

<form class="ingreso bmh-ingreso__form" method="POST" action="{{ route('user.login') }}" novalidate>
    @csrf

    <div class="bmh-ingreso__campo">
        <label for="input_type" class="visually-hidden">Usuario o email</label>
        <input id="input_type" class="bmh-ingreso__input" type="text" name="input_type" value="{{ old('input_type') }}"
               placeholder="Usuario o email" autocomplete="username" autocapitalize="off" spellcheck="false" required>
        <p class="username-error bmh-ingreso__error" role="alert" style="display:none"></p>
    </div>

    <div class="bmh-ingreso__campo">
        <label for="password" class="visually-hidden">Contraseña</label>
        <div class="bmh-ingreso__clave">
            <input id="password" class="bmh-ingreso__input" type="password" name="password"
                   placeholder="Contraseña" autocomplete="current-password" required>
            <button type="button" id="toggle-password" class="bmh-ingreso__ojo" aria-label="Mostrar la contraseña" aria-pressed="false">
                <svg width="26" height="26" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
                    <path d="M12.9833 10.0001C12.9833 11.6501 11.65 12.9834 10 12.9834C8.35 12.9834 7.01666 11.6501 7.01666 10.0001C7.01666 8.35006 8.35 7.01672 10 7.01672C11.65 7.01672 12.9833 8.35006 12.9833 10.0001Z" stroke="#c4c4c4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.99999 16.8916C12.9417 16.8916 15.6833 15.1583 17.5917 12.1583C18.3417 10.9833 18.3417 9.00831 17.5917 7.83331C15.6833 4.83331 12.9417 3.09998 9.99999 3.09998C7.05833 3.09998 4.31666 4.83331 2.40833 7.83331C1.65833 9.00831 1.65833 10.9833 2.40833 12.1583C4.31666 15.1583 7.05833 16.8916 9.99999 16.8916Z" stroke="#c4c4c4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 3l14 14" stroke="#c4c4c4" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <p class="password-error bmh-ingreso__error" role="alert" style="display:none"></p>
    </div>

    <div class="bmh-ingreso__acciones">
        <button id="nosotros-mas" type="submit" class="bmh-ingreso__boton">Iniciar sesión</button>
    </div>
</form>

<p class="bmh-ingreso__registro">¿No tenés usuario? <a href="{{ route('form-registro') }}">Registrate</a></p>

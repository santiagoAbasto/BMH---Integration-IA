<style>
    .input.ingreso label{
        font-family: 'Montserrat';
        font-size: 12px;
        font-weight: 500;
    }
    .input.ingreso input{
        border-radius: 58px;
        border: 1px solid #E5E5E5;
        box-shadow: none !important;
        padding-left: 18px;
        padding-right: 18px;
    }
</style>

<h3 class='pb-3' style="
color: #101010;
text-align: center;
font-family: Montserrat;
font-size: 20px;
font-style: normal;
font-weight: 700;
line-height: normal;">Iniciar sesión</h3>

<form class='input ingreso' method="POST" action="{{ route('user.login') }}">
    @csrf

    {{-- <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div> --}}

    <!-- Email or Username -->
    <div>
        <x-input-label for="input_type" :value="__('Nombre de usuario o email')" />
        <x-text-input style="border-radius: 10px; !important" id="input_type" placeholder="Usuario o email" class="form-control block mt-1 w-full" type="text" name="input_type" :value="old('input_type')"  autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
        {{-- <x-input-error :messages="$errors->get('email')" class="mt-2" /> --}}
        <div class='username-error mt-2' style='display:none;text-align:center;'></div>
    </div>

    <!-- Password -->
    <div class="mt-2">
        <x-input-label for="password" :value="__('Contraseña')" />
    
        <div class="password-input-container" style="position: relative;">
            <x-text-input 
                style="border-radius: 10px;" 
                placeholder="Contraseña" 
                id="password" 
                class="form-control block mt-1 w-full" 
                type="password" 
                name="password" 
                autocomplete="current-password" 
            />
    
            <!-- Botón de ojo para alternar la visibilidad de la contraseña -->
            <button type="button" id="toggle-password" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M12.9833 10.0001C12.9833 11.6501 11.65 12.9834 10 12.9834C8.35 12.9834 7.01666 11.6501 7.01666 10.0001C7.01666 8.35006 8.35 7.01672 10 7.01672C11.65 7.01672 12.9833 8.35006 12.9833 10.0001Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.99999 16.8916C12.9417 16.8916 15.6833 15.1583 17.5917 12.1583C18.3417 10.9833 18.3417 9.00831 17.5917 7.83331C15.6833 4.83331 12.9417 3.09998 9.99999 3.09998C7.05833 3.09998 4.31666 4.83331 2.40833 7.83331C1.65833 9.00831 1.65833 10.9833 2.40833 12.1583C4.31666 15.1583 7.05833 16.8916 9.99999 16.8916Z" stroke="#566571" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    
        <div class='password-error mt-2' style='display:none;text-align:center;'></div>
    </div>

    <!-- Remember Me -->
  <!-- Remember Me -->
<!-- Remember Me -->
<div class="block mt-4">
    <label for="remember_me" class="inline-flex items-center">
        <input id="remember_me" type="checkbox" style="display: inline" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
        <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme') }}</span>
    </label>
</div>



    <div class="flex items-center justify-end mt-3 d-flex justify-content-end">
        {{-- @if (Route::has('password.request'))
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                {{ __('¿Olvidó su contraseña?') }}
            </a>
        @endif --}}

        <button id='nosotros-mas' class="green-btn" style='width:100%;'>
            {{ __('Iniciar sesión') }}
        </button>
    </div>
</form>

<div class='border-top border-1 mt-3 pt-2' style='text-align:center;'>
    <a href="{{route('form-registro')}}"><span style='color: var(--Verde, #0098DA);
font-family: Montserrat;
font-size: 12px;
font-style: normal;
font-weight: 400;
line-height: normal;'>¿No tenés usuario?</span> <br><u><span style='
font-family: Montserrat;
font-size: 15px;
font-style: normal;
font-weight: 300;
line-height: normal;'>Regístrate</span></u></a>
</div>


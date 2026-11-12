{{-- Login del panel de administración (GET /login/admin → POST /admin/login), con admin-login.v1 de osole/ui.
     Un solo campo de usuario o email («input_type»): LoginAdminRequest lo valida como username o email,
     por eso los errores pueden volver en cualquiera de esas claves (email-error-keys). --}}
@php($logoPanel = \App\Models\Imagen::query()->where('sector', 'logo2')->first())
<!DOCTYPE html>
<html lang="es" class="adm adm-login">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Ingresar · Administrador BMH</title>
    @include('layouts.partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..700&family=Montserrat:wght@500..800&display=swap" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <main>
        <x-osole::admin-login.v1
            :action="route('admin.login')"
            :logo="$logoPanel?->path ? asset('imagenes/'.$logoPanel->path) : null"
            logo-alt="BMH"
            brand-name="BMH"
            panel-title="Panel de administración"
            panel-text="Catálogo, pedidos, clientes y contenido del sitio de BMH, en un solo lugar."
            :home-href="route('home')"
            title="Iniciar sesión"
            description="Ingresá con tu usuario o tu email de administrador."
            email-type="text"
            email-name="input_type"
            email-label="Usuario o email"
            email-placeholder="Tu usuario"
            :email-error-keys="['username', 'email']"
            :forgot-password-href="Route::has('password.request') ? route('password.request') : null"
            :status="session('status')"
        >
            <x-slot:panel-footer>BMH Distribuidora · Acceso exclusivo para administradores</x-slot:panel-footer>
            <x-slot:footer>© {{ date('Y') }} BMH Distribuidora</x-slot:footer>
        </x-osole::admin-login.v1>
    </main>
</body>
</html>

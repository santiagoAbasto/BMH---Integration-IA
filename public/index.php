<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
| Demo local sobre PHP 8.5 + Laravel 10: silencia E_DEPRECATED del framework y
| de los polyfills. No cambia comportamiento. Ver README §Requisitos.
*/
if (PHP_VERSION_ID >= 80400) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

/*
|--------------------------------------------------------------------------
| Raíz de la aplicación
|--------------------------------------------------------------------------
|
| En el hosting de BMH el código vive en un directorio hermano `bmh/` y
| `public/` es el document root. En un checkout local (y en `artisan serve`)
| la app está un nivel arriba. Se detecta cuál de los dos layouts es en vez de
| hardcodear uno y romper el otro.
|
*/

$base = is_file(__DIR__.'/../bmh/vendor/autoload.php')
    ? __DIR__.'/../bmh'
    : __DIR__.'/..';

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
*/

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/

require $base.'/vendor/autoload.php';

// Se precarga mientras E_DEPRECATED sigue enmascarado: Laravel restablece
// error_reporting(-1) al bootear y esta clase emite una deprecación en PHP 8.5.
class_exists(Illuminate\Log\Logger::class);

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/

$app = require_once $base.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);

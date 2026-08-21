<?php

declare(strict_types=1);

/*
| Bootstrap de la suite.
|
| Este checkout corre Laravel 10 sobre PHP 8.5, y buena parte del vendor
| (framework, polyfills, Collision, Termwind) todavía emite E_DEPRECATED por el
| cambio de nullable implícito. Sin enmascararlo acá, el ErrorHandler de PHPUnit
| intercepta deprecaciones lanzadas durante el autoload —fuera de cualquier
| test— y el printer de Collision se cae al intentar reportarlas.
|
| Enmascarar acá NO oculta deprecaciones del código de BMH: `<source>` en
| phpunit.xml tiene `restrictDeprecations="true"`, así que las que salgan de
| `app/` siguen reportándose.
*/
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';

// Se precargan mientras la máscara está activa: se compilan con firmas que PHP
// 8.5 considera deprecadas y contaminarían el primer test que las toque.
foreach ([
    Illuminate\Log\Logger::class,
    Illuminate\Container\Container::class,
    Illuminate\Support\Arr::class,
    Illuminate\Support\Str::class,
    Illuminate\Support\Collection::class,
    Illuminate\Database\QueryException::class,
    Illuminate\Database\Query\Builder::class,
    Illuminate\Database\Eloquent\Builder::class,
    Illuminate\Http\Request::class,
    Illuminate\Http\UploadedFile::class,
    Illuminate\Validation\Validator::class,
    Illuminate\Routing\Router::class,
    Illuminate\Session\Store::class,
] as $class) {
    class_exists($class);
}

<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\AssistantPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| BMH AI Technical Sales Advisor
|--------------------------------------------------------------------------
|
| Las rutas viven en el grupo `web` a propósito: el asesor usa la sesión de la
| Zona de Clientes que ya existe, no un segundo sistema de login. Eso también
| da CSRF gratis en los POST.
|
| Ver docs/architecture.md §"Autenticación".
*/

// La pantalla (Inertia).
Route::middleware(['cliente'])->group(function (): void {
    Route::get('/asesor', [AssistantPageController::class, 'show'])->name('assistant.page');
    Route::get('/asesor/{conversation}', [AssistantPageController::class, 'show'])
        ->whereNumber('conversation')
        ->name('assistant.page.conversation');
});

// La API que consume el chat.
Route::middleware(['customer.api', 'throttle:assistant'])
    ->prefix('api/assistant')
    ->name('assistant.')
    ->group(function (): void {
        Route::get('status', [AssistantController::class, 'status'])->name('status');

        Route::get('conversations', [AssistantController::class, 'index'])->name('conversations.index');
        Route::post('conversations', [AssistantController::class, 'store'])->name('conversations.store');
        Route::get('conversations/{conversation}', [AssistantController::class, 'show'])
            ->whereNumber('conversation')->name('conversations.show');

        Route::post('conversations/{conversation}/messages', [AssistantController::class, 'message'])
            ->whereNumber('conversation')->name('messages.store');
        Route::post('conversations/{conversation}/stream', [AssistantController::class, 'stream'])
            ->whereNumber('conversation')->name('messages.stream');

        Route::post('conversations/{conversation}/attachments', [AssistantController::class, 'attachmentStore'])
            ->whereNumber('conversation')->name('attachments.store');

        Route::post('conversations/{conversation}/feedback', [AssistantController::class, 'feedback'])
            ->whereNumber('conversation')->name('feedback.store');
        Route::post('conversations/{conversation}/handoff', [AssistantController::class, 'handoff'])
            ->whereNumber('conversation')->name('handoff.store');

        Route::get('attachments/{attachment}', [AssistantController::class, 'attachmentShow'])
            ->whereNumber('attachment')->name('attachment');
    });

// Catálogo de sólo lectura, para la ficha de producto del chat.
Route::middleware(['customer.api', 'throttle:assistant'])
    ->prefix('api/catalog')
    ->name('catalog.')
    ->group(function (): void {
        Route::get('categories', [\App\Http\Controllers\Api\CatalogController::class, 'categories'])->name('categories');
        Route::get('products/{product}', [\App\Http\Controllers\Api\CatalogController::class, 'show'])
            ->whereNumber('product')->name('products.show');
        Route::post('search', [\App\Http\Controllers\Api\CatalogController::class, 'search'])->name('search');
        Route::post('compare', [\App\Http\Controllers\Api\CatalogController::class, 'compare'])->name('compare');
    });

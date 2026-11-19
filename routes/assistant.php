<?php

declare(strict_types=1);

use App\Http\Controllers\AiLearningController;
use App\Http\Controllers\AiTestLabController;
use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\AssistantPageController;
use Illuminate\Support\Facades\Route;

// Laboratorio interno: usa la misma orquestación, pero sus conversaciones y
// evaluaciones quedan aisladas de la Zona de Clientes.
Route::middleware(['admin', 'throttle:assistant'])
    ->prefix('dashboard/ia/pruebas')
    ->name('admin.ai-tests.')
    ->group(function (): void {
        Route::get('/', [AiTestLabController::class, 'index'])->name('index');
        Route::post('conversations', [AiTestLabController::class, 'store'])->name('store');
        Route::get('conversations/{conversation}', [AiTestLabController::class, 'show'])
            ->whereNumber('conversation')->name('show');
        Route::post('conversations/{conversation}/messages', [AiTestLabController::class, 'message'])
            ->whereNumber('conversation')->name('messages.store');
        Route::post('conversations/{conversation}/attachments', [AiTestLabController::class, 'attachmentStore'])
            ->whereNumber('conversation')->name('attachments.store');
        Route::post('conversations/{conversation}/reviews', [AiTestLabController::class, 'review'])
            ->whereNumber('conversation')->name('reviews.store');
        Route::post('feedback/{feedback}/review', [AiTestLabController::class, 'reviewCustomerFeedback'])
            ->whereNumber('feedback')->name('feedback.review');
        Route::get('attachments/{attachment}', [AiTestLabController::class, 'attachmentShow'])
            ->whereNumber('attachment')->name('attachments.show');
    });

// Dirigir al asesor: enseñarle qué foto es qué código, cuentas demo y métricas.
Route::middleware(['admin'])
    ->prefix('dashboard/ia/aprendizaje')
    ->name('admin.ai-learning.')
    ->group(function (): void {
        Route::get('/', [AiLearningController::class, 'index'])->name('index');
        Route::post('cuentas-demo', [AiLearningController::class, 'storeDemoAccount'])->name('demo.store');
        Route::post('cuentas-demo/{account}/alternar', [AiLearningController::class, 'toggleDemoAccount'])
            ->whereNumber('account')->name('demo.toggle');
        Route::delete('cuentas-demo/{account}', [AiLearningController::class, 'destroyDemoAccount'])
            ->whereNumber('account')->name('demo.destroy');
        Route::post('ensenar', [AiLearningController::class, 'teach'])->name('teach');
        Route::post('reglas/{rule}/alternar', [AiLearningController::class, 'toggleRule'])
            ->whereNumber('rule')->name('rules.toggle');
        Route::delete('reglas/{rule}', [AiLearningController::class, 'destroyRule'])
            ->whereNumber('rule')->name('rules.destroy');
        Route::get('reglas/{rule}/imagen', [AiLearningController::class, 'ruleImage'])
            ->whereNumber('rule')->name('rules.image');

        // Base de conocimiento: términos, reglas y notas que el modelo lee.
        Route::post('conocimiento', [AiLearningController::class, 'storeKnowledge'])->name('knowledge.store');
        Route::put('conocimiento/{entry}', [AiLearningController::class, 'updateKnowledge'])
            ->whereNumber('entry')->name('knowledge.update');
        Route::post('conocimiento/{entry}/alternar', [AiLearningController::class, 'toggleKnowledge'])
            ->whereNumber('entry')->name('knowledge.toggle');
        Route::post('conocimiento/{entry}/aprobar', [AiLearningController::class, 'approveKnowledge'])
            ->whereNumber('entry')->name('knowledge.approve');
        Route::post('conocimiento/{entry}/rechazar', [AiLearningController::class, 'rejectKnowledge'])
            ->whereNumber('entry')->name('knowledge.reject');
        Route::delete('conocimiento/{entry}', [AiLearningController::class, 'destroyKnowledge'])
            ->whereNumber('entry')->name('knowledge.destroy');
        Route::get('conocimiento/{entry}/imagen', [AiLearningController::class, 'knowledgeImage'])
            ->whereNumber('entry')->name('knowledge.image');
    });

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
Route::middleware(['cliente', 'assistant.access'])->group(function (): void {
    Route::get('/asesor', [AssistantPageController::class, 'show'])->name('assistant.page');
    Route::get('/asesor/{conversation}', [AssistantPageController::class, 'show'])
        ->whereNumber('conversation')
        ->name('assistant.page.conversation');
});

// La API que consume el chat.
Route::middleware(['customer.api', 'assistant.access', 'throttle:assistant'])
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
        Route::post('conversations/{conversation}/teach', [AssistantController::class, 'teach'])
            ->whereNumber('conversation')->name('teach');
        Route::post('conversations/{conversation}/cart-items', [AssistantController::class, 'cartItemStore'])
            ->whereNumber('conversation')->name('cart-items.store');
        Route::post('conversations/{conversation}/handoff', [AssistantController::class, 'handoff'])
            ->whereNumber('conversation')->name('handoff.store');

        Route::get('attachments/{attachment}', [AssistantController::class, 'attachmentShow'])
            ->whereNumber('attachment')->name('attachment');
    });

// Catálogo de sólo lectura, para la ficha de producto del chat.
Route::middleware(['customer.api', 'assistant.access', 'throttle:assistant'])
    ->prefix('api/catalog')
    ->name('catalog.')
    ->group(function (): void {
        Route::get('categories', [\App\Http\Controllers\Api\CatalogController::class, 'categories'])->name('categories');
        Route::get('products/{product}', [\App\Http\Controllers\Api\CatalogController::class, 'show'])
            ->whereNumber('product')->name('products.show');
        Route::post('search', [\App\Http\Controllers\Api\CatalogController::class, 'search'])->name('search');
        Route::post('compare', [\App\Http\Controllers\Api\CatalogController::class, 'compare'])->name('compare');
    });

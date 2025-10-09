<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Produtos
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    // Editar dados
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');

    // Editar PDF
    Route::get('/products/{product}/edit-pdf', [ProductController::class, 'editPdf'])->name('products.editPdf');
    Route::put('/products/{product}/pdf', [ProductController::class, 'updatePdf'])->name('products.updatePdf');
    Route::post('/products/{product}/pdf', [ProductController::class, 'updatePdf'])->name('products.updatePdf');

    // Deletar
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Aprovar e Recusar
    Route::put('/products/{product}/approve', [ProductController::class, 'approveEvidency'])->name('products.approveEvidency');
    Route::put('/products/{product}/reject', [ProductController::class, 'rejectEvidency'])->name('products.rejectEvidency');

    // Dashboards
    Route::get('/dashboard', [ProductController::class, 'dashboard'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    // routes/web.php

    // Apenas ADM pode gerenciar usuários
    Route::middleware(['auth', 'isAdmin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Histórico de PDFs
    Route::get('/products/{product}/pdf-history', [ProductController::class, 'pdfHistory'])
        ->name('products.pdfHistory');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FournisseurController;

// Client routes
Route::prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('/{client}', [ClientController::class, 'show']);
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::delete('/{client}', [ClientController::class, 'destroy']);
});

// Article routes
Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('/stats', [ArticleController::class, 'stats']);
    Route::get('/{article}', [ArticleController::class, 'show']);
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::delete('/{article}', [ArticleController::class, 'destroy']);
});

// Fournisseur routes
Route::prefix('fournisseurs')->group(function () {
    Route::get('/', [FournisseurController::class, 'index']);
    Route::post('/', [FournisseurController::class, 'store']);
    Route::get('/{fournisseur}', [FournisseurController::class, 'show']);
    Route::put('/{fournisseur}', [FournisseurController::class, 'update']);
    Route::delete('/{fournisseur}', [FournisseurController::class, 'destroy']);
});

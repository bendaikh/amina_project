<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ParametreController;

// Client routes
Route::prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('/{client}', [ClientController::class, 'show']);
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::delete('/{client}', [ClientController::class, 'destroy']);
    Route::post('/{client}/articles', [ClientController::class, 'assignArticles']);
    Route::get('/{client}/articles', [ClientController::class, 'getArticles']);
    Route::post('/{client}/toggle-active', [ClientController::class, 'toggleActive']);
});

// Article routes
Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::get('/stats', [ArticleController::class, 'stats']);
    Route::get('/{article}', [ArticleController::class, 'show']);
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::delete('/{article}', [ArticleController::class, 'destroy']);
    Route::post('/{article}/duplicate', [ArticleController::class, 'duplicate']);
    Route::post('/{article}/toggle-active', [ArticleController::class, 'toggleActive']);
});

// Fournisseur routes
Route::prefix('fournisseurs')->group(function () {
    Route::get('/', [FournisseurController::class, 'index']);
    Route::post('/', [FournisseurController::class, 'store']);
    Route::get('/{fournisseur}', [FournisseurController::class, 'show']);
    Route::put('/{fournisseur}', [FournisseurController::class, 'update']);
    Route::delete('/{fournisseur}', [FournisseurController::class, 'destroy']);
});

// Parametre routes
Route::prefix('parametres')->group(function () {
    Route::get('/', [ParametreController::class, 'index']);
    Route::get('/types', [ParametreController::class, 'getTypes']);
    Route::get('/all-for-forms', [ParametreController::class, 'getAllForForms']);
    Route::get('/type/{type}', [ParametreController::class, 'getByType']);
    Route::get('/sous-familles/{familleId}', [ParametreController::class, 'getSousFamilles']);
    Route::post('/', [ParametreController::class, 'store']);
    Route::put('/{parametre}', [ParametreController::class, 'update']);
    Route::delete('/{parametre}', [ParametreController::class, 'destroy']);
    Route::post('/{parametre}/toggle-active', [ParametreController::class, 'toggleActive']);
    Route::post('/reorder', [ParametreController::class, 'reorder']);
});

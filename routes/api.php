<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Versionnement des API
Route::prefix('v1')->group(function () {
    // Routes d'authentification (sans middleware auth)
    Route::post('auth/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('auth/refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('auth/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->middleware('auth:api');


    // Routes pour les comptes bancaires SANS authentification pour les tests et démo
    Route::group([], function () {
            Route::get('comptes', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'index']);
            Route::post('comptes', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'store']);
            Route::get('comptes/{id}', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'show'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
            Route::put('comptes/{id}', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'update'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
            Route::delete('comptes/{id}', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'destroy'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

            // Routes spécifiques pour le blocage des comptes épargne
            Route::post('comptes/{id}/bloquer', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'bloquer'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
            Route::post('comptes/{id}/debloquer', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'debloquer'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

            // Routes pour l'archivage des comptes
            Route::post('comptes/{id}/archiver', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'archiver'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
            Route::post('comptes/{id}/desarchiver', [App\Http\Controllers\Api\V1\ComptesBancairesController::class, 'desarchiver'])->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        });

});


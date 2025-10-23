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
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Exemples de routes suivant les conventions
    // Utiliser des noms au pluriel, minuscules avec tirets
    Route::apiResource('comptes-bancaires', 'App\Http\Controllers\Api\V1\ComptesBancairesController');
    Route::apiResource('transactions', 'App\Http\Controllers\Api\V1\TransactionsController');
    Route::apiResource('clients', 'App\Http\Controllers\Api\V1\ClientsController');
});

<?php

use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route pour la documentation Swagger
Route::get('/api/documentation', function () {
    $documentation = 'default';

    // Génère l'URL vers le fichier JSON Swagger
    $urlToDocs = env('APP_ENV') === 'production' ? secure_url('/storage/api-docs.json') : url('/storage/api-docs.json');

    $operationsSorter = config('l5-swagger.defaults.operations_sort');
    $configUrl = config('l5-swagger.defaults.additional_config_url');
    $validatorUrl = config('l5-swagger.defaults.validator_url');
    $useAbsolutePath = config('l5-swagger.defaults.paths.use_absolute_path');

    return view('vendor.l5-swagger.index', compact(
        'documentation',
        'urlToDocs',
        'operationsSorter',
        'configUrl',
        'validatorUrl',
        'useAbsolutePath'
    ));
});

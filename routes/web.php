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
Route::get('/maryvonne/documentation', function () {
    $documentation = 'default';

    // Génère l'URL vers le fichier JSON Swagger
    $urlToDocs = 'http://localhost:8000/storage/api-docs.json';

    // Corrige le protocole selon l’environnement
    if (app()->environment('production')) {
        $urlToDocs = preg_replace('#^http:#', 'https:', $urlToDocs);
    } else {
        $urlToDocs = preg_replace('#^https:#', 'http:', $urlToDocs);
    }

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

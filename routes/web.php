<?php

use Illuminate\Support\Facades\Route;

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
    $urlToDocs = route('l5-swagger.'.$documentation.'.docs');
    $operationsSorter = config('l5-swagger.defaults.operations_sort');
    $configUrl = config('l5-swagger.defaults.additional_config_url');
    $validatorUrl = config('l5-swagger.defaults.validator_url');
    $useAbsolutePath = config('l5-swagger.defaults.paths.use_absolute_path');

    // Forcer l'URL HTTPS pour le déploiement
    if (app()->environment('production')) {
        $urlToDocs = str_replace('http://', 'https://', $urlToDocs);
        $urlToDocs = str_replace('http://gestion-bancairee-5.onrender.com', 'https://gestion-bancairee-5.onrender.com', $urlToDocs);
        $urlToDocs = str_replace('http://localhost:8000', 'https://gestion-bancairee-5.onrender.com', $urlToDocs);
    } else {
        // Pour le développement local, garder HTTP
        $urlToDocs = str_replace('https://', 'http://', $urlToDocs);
    }

    return view('vendor.l5-swagger.index', compact('documentation', 'urlToDocs', 'operationsSorter', 'configUrl', 'validatorUrl', 'useAbsolutePath'));
});


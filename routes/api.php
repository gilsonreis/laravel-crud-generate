<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Carrega automaticamente todas as rotas CRUD em app/Routes
foreach (glob(app_path('Routes/*.php')) as $routeFile) {
    require $routeFile;
}

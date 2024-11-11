<?php

use Illuminate\Support\Facades\Route;
use App\Http\Actions\Product\ProductGetAllAction;
use App\Http\Actions\Product\ProductShowAction;
use App\Http\Actions\Product\ProductCreateAction;
use App\Http\Actions\Product\ProductUpdateAction;
use App\Http\Actions\Product\ProductDeleteAction;

Route::prefix('products')
    ->name('product.')
    ->group(function () {
        Route::get('/', ProductGetAllAction::class)->name('index');
        Route::get('/{id}', ProductShowAction::class)->name('show');
        Route::post('/', ProductCreateAction::class)->name('store');
        Route::put('/{id}', ProductUpdateAction::class)->name('update');
        Route::delete('/{id}', ProductDeleteAction::class)->name('destroy');
    });

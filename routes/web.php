<?php

use Illuminate\Support\Facades\Route;
use Naimul\DbVisualizer\Http\Controllers\CacheController;
use Naimul\DbVisualizer\Http\Controllers\ModelController;
use Naimul\DbVisualizer\Http\Controllers\VisualizerController;

Route::get('/', VisualizerController::class)->name('index');
Route::get('/data', [ModelController::class, 'index'])->name('models');
Route::get('/detail/{model}', [ModelController::class, 'show'])->where('model', '[A-Za-z0-9\\\\]+')->name('models.show');
Route::post('/cache-clear', CacheController::class)->middleware('throttle:5,1')->name('cache.clear');

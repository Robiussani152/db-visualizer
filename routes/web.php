<?php

use Illuminate\Support\Facades\Route;
use Naimul\DbVisualizer\Http\Controllers\VisualizerController;

Route::get('/', [VisualizerController::class, 'index'])->name('index');
Route::get('/data', [VisualizerController::class, 'data'])->name('data');
Route::get('/detail/{model}', [VisualizerController::class, 'detail'])->name('detail');
Route::post('/cache-clear', [VisualizerController::class, 'clearCache'])->name('clear-cache');

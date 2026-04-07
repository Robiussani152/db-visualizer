<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'VisualizerController@index');
Route::get('/data', 'VisualizerController@data');
Route::get('/detail/{model}', 'VisualizerController@detail');
Route::post('/cache-clear', 'VisualizerController@clearCache');

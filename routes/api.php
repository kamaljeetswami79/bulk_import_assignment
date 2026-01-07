<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/import-csv', [ImportController::class, 'import']);

Route::post('/upload/initiate', [UploadController::class, 'initiate']);
Route::post('/upload/{uuid}/chunk', [UploadController::class, 'uploadChunk']);
Route::post('/upload/{uuid}/complete', [UploadController::class, 'complete']);

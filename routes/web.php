<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoFrameController;

Route::get('/', [VideoFrameController::class, 'index'])
    ->name('frames.index');

Route::post('/extract', [VideoFrameController::class, 'extract'])
    ->name('frames.extract');

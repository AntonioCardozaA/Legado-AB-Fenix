<?php

use App\Http\Controllers\Api\PowerBiController;
use App\Http\Middleware\PowerBiApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware(PowerBiApiKey::class)
    ->get('/powerbi/52-12-4', [PowerBiController::class, 'analisis52124'])
    ->name('api.powerbi.52-12-4');

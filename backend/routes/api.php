<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ContactController;

Route::post('/contact', [ContactController::class, 'send']);
/*
//TEMP: simplest contact endpoint to confirm no 500s
Route::post('/contact', function () {
    return response()->json(['message' => 'ok'], 201);
});
*/
Route::get('/health', function () {
    try {
        \DB::connection()->getPdo();
        $db = true;
    } catch (\Exception $e) {
        $db = false;
    }

    return response()->json([
        'status' => $db ? 'ok' : 'degraded',
        'db' => $db,
        'timestamp' => now()->toISOString(),
    ], $db ? 200 : 500);
});

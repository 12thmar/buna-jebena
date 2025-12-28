<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SmokeTestController;
use App\Http\Controllers\NewsletterController;


Route::get('/health', fn () => response()->json(['ok' => true]));
Route::post('/contact', [ContactController::class, 'send'])
    ->middleware('throttle:5,1'); // 5 emails per minute per IP
Route::post('/smoke/email', [SmokeTestController::class, 'email']);

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);
    /*
//TEMP: simplest contact endpoint to confirm no 500s
Route::post('/contact', function () {
    return response()->json(['message' => 'ok'], 201);
});
*/

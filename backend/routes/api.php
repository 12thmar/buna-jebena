<?php

use Illuminate\Support\Facades\Route;
<<<<<<< HEAD
use App\Http\Controllers\ContactController;


Route::get('/health', fn() => response()->json(['ok'=>true]));
Route::post("/contact", [ContactController::class, "submit"]);
=======
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ContactController;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::post('/contact', [ContactController::class, 'send']);
/*
//TEMP: simplest contact endpoint to confirm no 500s
Route::post('/contact', function () {
    return response()->json(['message' => 'ok'], 201);
});
*/
>>>>>>> add-contact

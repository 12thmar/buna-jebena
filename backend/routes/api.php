<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;


Route::get('/health', fn() => response()->json(['ok'=>true]));
Route::post("/contact", [ContactController::class, "submit"]);

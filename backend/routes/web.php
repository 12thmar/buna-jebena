<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
<<<<<<< HEAD
=======

Route::get('/mail-test', function () {
    try {
        Mail::raw('This is a Mailpit test', function ($m) {
            $m->to('you@example.com')->subject('Mailpit test');
        });
        return '✅ Mail sent (check Mailpit)';
    } catch (Throwable $e) {
        return '❌ Mail failed: ' . $e->getMessage();
    }
});
>>>>>>> add-contact

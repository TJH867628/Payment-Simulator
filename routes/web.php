<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('homepage/homePage');
});
Route::get('/dashboard', function () {
    return view('homepage/dashboard');
});

<?php

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('homepage/homePage');
});
Route::get('/dashboard', function () {
    return view('homepage/dashboard');
});

Route::get('/topup/status', function () {
    return view('homepage/topUpStatusPage');
});

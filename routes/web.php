<?php

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('homepage/homePage');
});
Route::get('/dashboard', function () {
    return view('homepage/dashboard');
});

Route::get('/topup/success', function () {
    return view('homepage/topUpSuccessPage');
});

Route::get('/topup/fail', function () {
    return view('homepage/topUpFailPage');
});

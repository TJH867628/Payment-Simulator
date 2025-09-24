<?php

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('homePage');
});
Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/topup/status', function () {
    return view('topUpStatusPage');
});

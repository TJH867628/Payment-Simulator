<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/wallet/{userId}', [WalletController::class, 'getWalletByUserId']);
Route::get('/transactions/{walletId}', [WalletController::class, 'getTransactionHistory']);
Route::post('/wallet/{walletId}/topup', [WalletController::class, 'topUp']);  
Route::post('/topup/callback', [WalletController::class, 'topUpCallback']);
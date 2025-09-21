<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transactions;

class WalletController extends Controller
{
    //
    public function getWalletByUserId($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return response()->json(['message' => 'User wallet info not found'], 404);
        }

        return response()->json(['wallet' => $wallet], 200);
    }

    public function getTransactionHistory($walletId)
    {
        $transactions = Transactions::where('wallet_id', $walletId)->get();
        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'No transactions found for this wallet'], 404);
        }
        return response()->json(['transactions' => $transactions], 200);
    }
}

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

    public function topUp(Request $request, $walletId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        // Step 1: Prepare ToyyibPay payload
        $payload = [
            'userSecretKey' => '71iar8tc-l8u2-51fu-2whc-giva85ka529j',
            'categoryCode' => 't3cabxsj',
            'billName' => 'Wallet Top Up #' . $wallet->id,
            'billDescription' => $validated['description'] ?? 'Wallet top-up',
            'billPriceSetting' => 0,
            'billPayorInfo' => 1,
            'billAmount' => $validated['amount'] * 100, // ToyyibPay uses cents
            'billReturnUrl' => 'http://127.0.0.1:8000/topup/success',
            'billCallbackUrl' => 'http://127.0.0.1:8000/api/topup/callback',
            'billExternalReferenceNo' => uniqid('TXN_'),
            'billTo' => $wallet->user->name ?? 'Customer',
            'billEmail' => $wallet->user->email ?? 'customer@example.com',
            'billPhone' => $wallet->user->phone_number ?? '0100000000',
            'billPaymentChannel' => 0,
            'billChargeToCustomer' => 1,
        ];

        // Step 2: Call ToyyibPay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);
        dd($result);
        if (!$response || isset($response['error'])) {
            return response()->json(['message' => 'Failed to create bill'], 500);
        }

        // Step 3: Store transaction with "pending" status
        $transaction = new Transactions();
        $transaction->wallet_id = $walletId;
        $transaction->amount = $validated['amount'];
        $transaction->type = 'top-up';
        $transaction->description = $validated['description'] ?? 'Wallet top-up';
        $transaction->status = 'pending'; // wait until ToyyibPay callback
        $transaction->save();

        return response()->json([
            'message' => 'Bill created, redirect customer to ToyyibPay',
            'bill' => $response,
            'transaction' => $transaction
        ], 200);
    }

    public function topUpCallBack(Request $request)
    {
        // Validate the callback data
        $billCode = $request->input('billcode');
        $status = $request->input('status');
        $transactionId = $request->input('order_id');

        // Find the transaction
        $transaction = Transactions::where('id', $transactionId)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Update transaction status based on ToyyibPay response
        if ($status == '1') { // Payment successful
            $transaction->status = 'completed';
            
            // Update wallet balance
            $wallet = Wallet::find($transaction->wallet_id);
            if ($wallet) {
                $wallet->balance += $transaction->amount;
                $wallet->save();
            }
        } else {
            $transaction->status = 'failed';
        }

        $transaction->save();

        return response()->json(['message' => 'Callback processed successfully'], 200);
    }
}

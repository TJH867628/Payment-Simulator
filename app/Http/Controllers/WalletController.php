<?php

namespace App\Http\Controllers;

use GrahamCampbell\ResultType\Success;
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
            return response()->json(['success' => true,'status' => 'NotFound','message' => 'No transactions found for this wallet'], status: 200);
        }
        return response()->json(['success' => true,'status' => 'Found','transactions' => $transactions], 200);
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
        $externalRef = uniqid('TXN_');
        // Step 1: Prepare ToyyibPay payload
        $payload = [
            'userSecretKey' => '71iar8tc-l8u2-51fu-2whc-giva85ka529j',
            'categoryCode' => 't3cabxsj',
            'billName' => 'Wallet Top Up #' . $wallet->id,
            'billDescription' => $validated['description'] ?? 'Wallet top-up',
            'billPriceSetting' => 0,
            'billPayorInfo' => 1,
            'billAmount' => $validated['amount'] * 100, // ToyyibPay uses cents
            'billReturnUrl' => 'http://127.0.0.1:8000/topup/status',
            'billCallbackUrl' => 'http://127.0.0.1:8000/api/topup/callback',
            'billExternalReferenceNo' => $externalRef,
            'billTo' => $wallet->user->name ?? 'Customer',
            'billEmail' => $wallet->user->email ?? 'customer@example.com',
            'billPhone' => $wallet->user->phone_number ?? '0100000000',
            'billPaymentChannel' => 0,
            'billChargeToCustomer' => 1,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (!$response || isset($response['error'])) {
             return view('homepage/topUpFailedPage', [
                'message' => 'Failed to create bill. Please try again.'
            ]);
        }

        $transaction = new Transactions();
        $transaction->wallet_id = $walletId;
        $transaction->amount = $validated['amount'];
        $transaction->type = 'top-up';
        $transaction->description = $validated['description'] ?? 'Wallet top-up';
        $transaction->billcode = $response[0]['BillCode'] ?? null; // ToyyibPay bill code
        $transaction->status = 'pending'; // wait until ToyyibPay callback
        $transaction->save();

        return response()->json([
            'message' => 'Bill created, redirect customer to ToyyibPay',
            'bill' => $response,
            'transaction' => $transaction
        ], 200);
    }

   public function topUpStatus($billCode)
    {
        // Find local transaction first
        $transaction = Transactions::where('billcode', $billCode)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Prepare payload for ToyyibPay API
        $payload = [
            'billCode' => $billCode
        ];

        // Call ToyyibPay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/getBillTransactions');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (!$response || !isset($response[0])) {
            return response()->json(['message' => 'Failed to fetch status from ToyyibPay'], 500);
        }

        $bill = $response[0];
        $statusCode = $bill['billpaymentStatus']; // 1=success, 2=pending, 3=failed
        $amount = $bill['billpaymentAmount'];

        // Update transaction and wallet based on status
        if ($statusCode == '1') { // Success
            if ($transaction->status !== 'completed') { // Only process if not already completed
                $transaction->status = 'completed';
                $transaction->save();

                $wallet = Wallet::find($transaction->wallet_id);
                if ($wallet) {
                    $wallet->balance += $transaction->amount;
                    $wallet->save();
                }
            }
        } elseif ($statusCode == '3') { // Failed
            $transaction->status = 'failed';
            $transaction->save();
        } else { // Pending
            $transaction->status = 'pending';
            $transaction->save();
        }

        return response()->json([
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'billCode' => $transaction->billcode,
            'toyyibAmount' => $amount
        ]);
    }
}

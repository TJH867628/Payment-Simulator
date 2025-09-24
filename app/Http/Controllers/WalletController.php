<?php

namespace App\Http\Controllers;

use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transactions;

class WalletController extends Controller
{
    //Get wallet info by user id
    public function getWalletByUserId($userId)
    {
        //Find user by id
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        //Find wallet by user id
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return response()->json(['message' => 'User wallet info not found'], 404);
        }

        return response()->json(['wallet' => $wallet], 200);
    }

    //Get transaction history by wallet id
    public function getTransactionHistory($walletId)
    {
        //Get all transactions for the wallet
        $transactions = Transactions::where('wallet_id', $walletId)->get();
        if ($transactions->isEmpty()) {
            return response()->json(['success' => true,'status' => 'NotFound','message' => 'No transactions found for this wallet'], status: 200);
        }
        return response()->json(['success' => true,'status' => 'Found','transactions' => $transactions], 200);
    }

    //Wallet top-up using ToyyibPay
    public function topUp(Request $request, $walletId)
    {
        //Validate input
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        //Find wallet
        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }
        $externalRef = uniqid('TXN_');
        //Prepare ToyyibPay payload
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

        //Call ToyyibPay API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        //Parse response
        $response = json_decode($result, true);

        //Handle API errors
        if (!$response || isset($response['error'])) {
             return view('homepage/topUpFailedPage', [
                'message' => 'Failed to create bill. Please try again.'
            ]);
        }

        //Record transaction as pending
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

    //Check top-up status by bill code
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

    //Transfer funds between users by phone number
    public function transferFunds(Request $request)
    {
        //Validate input
        $validated = $request->validate([
            'from_phone' => 'required|string',
            'to_phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        //Find users and wallets
        $fromUser = User::where('phone_number', $validated['from_phone'])->first();
        $toUser = User::where('phone_number', $validated['to_phone'])->first();

        //Error handling
        if (!$fromUser || !$toUser) {
            return response()->json(['message' => 'One or both users not found'], 404);
        }

        //Get wallets
        $fromWallet = Wallet::where('user_id', $fromUser->id)->first();
        $toWallet = Wallet::where('user_id', $toUser->id)->first();

        if (!$fromWallet || !$toWallet) {
            return response()->json(['message' => 'One or both wallets not found'], 404);
        }

        //Error handling
        if ($fromWallet->balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        // Deduct from sender
        $fromWallet->balance -= $validated['amount'];
        $fromWallet->save();

        // Add to receiver
        $toWallet->balance += $validated['amount'];
        $toWallet->save();

        // Record transactions
        $transactionOut = new Transactions();
        $transactionOut->wallet_id = $fromWallet->id;
        $transactionOut->amount = $validated['amount'];
        $transactionOut->type = 'transfer-out';
        $transactionOut->description = $validated['description'] ?? 'Transfer to ' . $toUser->name;
        $transactionOut->status = 'completed';
        $transactionOut->save();

        $transactionIn = new Transactions();
        $transactionIn->wallet_id = $toWallet->id;
        $transactionIn->amount = $validated['amount'];
        $transactionIn->type = 'transfer-in';
        $transactionIn->description = $validated['description'] ?? 'Transfer from ' . $fromUser->name;
        $transactionIn->status = 'completed';
        $transactionIn->save();

        return response()->json([
            'message' => 'Transfer successful',
            'from_wallet_balance' => $fromWallet->balance,
            'to_wallet_balance' => $toWallet->balance,
            'transaction_out' => $transactionOut,
            'transaction_in' => $transactionIn
        ], 200);
    }
}

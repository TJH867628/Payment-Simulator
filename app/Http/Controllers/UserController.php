<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;

class UserController extends Controller
{
    //Register new user
    public function register(Request $request)
    {
        //Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone_number' => 'required|string|max:15',
            'password' => 'required|string|min:8',
        ]);

        //Check if email already used or phone number already used
        if(User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'field' => 'email',
                'message' => 'Email already exists!'
            ], 200);
        }else if(User::where('phone_number', $validated['phone_number'])->exists()) {
            return response()->json([
                'success' => false,
                'field' => 'phone_number',
                'message' => 'Phone number already exists!'
            ], 200);
        }

        //Create a new user
        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'];
        $user->password = Hash::make($validated['password']);//Hash the password before saving
        $user->save();

        //Create a wallet for the new user
        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->balance = 0.00;
        $wallet->currency = 'MYR';
        $wallet->save();

        //Return success response
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully!',
            'user' => $user
        ], 201);
    }


    //Login user with email/phone number and password
    public function login(Request $request)
    {
        $account = $request->input('account');
        $password = $request->input('password');

        //Find user by email or phone number
        $user = User::where('email', $account)->orWhere('phone_number', $account)->first();

        //Check if user exists and password matches
        if($user && Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid credentials!'
            ], 401);
        }
    }

    //Verify user password by user id
    public function verifyPassword(Request $request)
    {
        $id = $request->validate([
            'id' => 'required|integer',
            'password' => 'required|string|min:8',
        ])['id'];
        $user = User::find($id);
        if (!$user) { 
            return response()->json(['message' => 'User not found'], 404);
        }

        $isValid = Hash::check($request->input('password'), $user->password);
        return response()->json(['valid' => $isValid], 200);
    }

    public function getUserByPhonenumber(Request $request, $phone_number)
    {
        //Find user by phone number
        $user = User::where('phone_number', $phone_number)->first();

        if ($user) {
            return response()->json([
                'success' => true,
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found!'
            ], 404);
        }
    }
}

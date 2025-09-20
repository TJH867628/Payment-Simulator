<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone_number' => 'required|string|max:15',
            'password' => 'required|string|min:8',
        ]);

        if(User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'field' => 'email',
                'message' => 'Email already exists!'
            ], 409);
        }else if(User::where('phone_number', $validated['phone_number'])->exists()) {
            return response()->json([
                'success' => false,
                'field' => 'phone_number',
                'message' => 'Phone number already exists!'
            ], 409);
        }

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'];
        $user->password = hash('sha512', $validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully!',
            'user' => $user
        ], 201);
    }


    public function login(Request $request)
    {
        $account = $request->input('account');
        $password = hash('sha512',$request->input('password'));

        $user = User::where('email', $account)->orWhere('phone_number', $account)->first();

        if($user && $user->password === $password) {
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
}

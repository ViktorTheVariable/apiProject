<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        try {
            // Create a new user in the database if the validation passes
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'User successfully registered'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            // Check if the email is correct and the provided password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                // if either email or password is incorrect
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->CreateToken('access_token');

            // if the login is successful, send access token to the user
            return response()->json([
                'access_token' => $token->plainTextToken
            ], 200);
        } catch (\Exception $e) {
            // if any other error occurs
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        // Delete all tokens associated with the user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
}

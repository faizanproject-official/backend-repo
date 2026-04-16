<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'address' => $request->address ?? null,
            'country' => $request->country ?? null,
            'state' => $request->state ?? null,
            'city' => $request->city ?? null,
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = trim(strtolower($request->email));
        $password = $request->password;

        \Illuminate\Support\Facades\Log::info('Login Attempt', [
            'original_email' => $request->email,
            'trimmed_email' => $email,
            'password_length' => strlen($password)
        ]);

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
             \Illuminate\Support\Facades\Log::warning('Auth::attempt failed for ' . $email);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Handle change password (public).
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Invalid email or old password'], 401);
        }

        $user->forceFill([
            'password' => $request->new_password
        ])->setRememberToken(\Illuminate\Support\Str::random(60));
        
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}

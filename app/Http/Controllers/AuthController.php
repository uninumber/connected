<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            "nickname" => ["required", "string", "max:40", "unique:users"],
            "password" => ["required", "string", "min:8", "confirmed"],
        ]);

        $user = User::create([
            "nickname" => $request->nickname,
            "password" => Hash::make($request->password),
        ]);

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json(
            [
                "user" => $user,
                "token" => $token,
                "message" => "Registration successful",
            ],
            201,
        );
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            "nickname" => ["required", "string", "max:40"],
            "password" => ["required"],
        ]);

        if (Auth::attempt($credentials, $request->boolean("remember"))) {
            $user = Auth::user();
            $token = $user->createToken("auth_token")->plainTextToken;

            return response()->json([
                "user" => $user,
                "token" => $token,
                "message" => "Login successful",
            ]);
        }

        throw ValidationException::withMessages([
            "nickname" => [
                "The provided credentials do not match our records.",
            ],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(["message" => "Logged out successfully"]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}

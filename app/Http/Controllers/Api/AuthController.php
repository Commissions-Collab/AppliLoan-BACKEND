<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequests;
use App\Http\Requests\RegisterRequests;
use App\Models\member;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    /**
     * Register a new user.
     * This method handles the registration of a new user. Only students can register.
     * It creates a new user with the provided details and returns a JSON response with the user
     * @param \App\Http\Requests\RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequests $request)
    {
        $data = $request->validated();

    DB::beginTransaction();

    try {
        // Create the user
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_verified' => false,
        ]);

        // Create member only if role is 'member'
        if ($data['role'] === 'member') {
            member::create([
                'user_id' => $user->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'address' => $data['address'],
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Registration failed',
            'error' => $e->getMessage()
        ], 500);
    }
    }
     /**
     * This is not redirecting the user to their dashboard after login.
     * You can handle the redirection in your frontend after receiving the response.
     * @param \App\Http\Requests\LoginRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */

    public function login(LoginRequests $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->input('email'))->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout the user and delete the access token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }
}

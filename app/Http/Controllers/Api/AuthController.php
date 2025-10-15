<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequests;
use App\Http\Requests\RegisterRequests;
use App\Mail\VerificationCodeMail;
use App\Models\member;
use App\Models\MemberLogin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function register(RegisterRequests $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $verificationCode = Str::random(6);
            // Create the user
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_verified' => false,
                'is_member' => false,
                'verification_code' => $verificationCode,
                'verification_code_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully. Please check your email for a verification code.',
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

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'verification_code' => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
            ->where('verification_code', $request->verification_code)
            ->where('verification_code_expires_at', '>', Carbon::now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid verification code or email.'], 400);
        }

        $user->email_verified_at = Carbon::now();
        $user->is_verified = true;
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }

    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        $verificationCode = Str::random(6);
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return response()->json(['message' => 'A new verification code has been sent to your email.'], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|string|min:6|max:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
            ->where('verification_code', $request->verification_code)
            ->where('verification_code_expires_at', '>', Carbon::now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid verification code or email.'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Password changed successfully.'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $verificationCode = Str::random(6);
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return response()->json(['message' => 'A verification code has been sent to your email.'], 200);
    }

    public function login(LoginRequests $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->input('email'))->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->isMember() && $user->member) {
            MemberLogin::create([
                'member_id' => $user->member->id,
                'login_at' => now()
            ]); 
        }

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
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}

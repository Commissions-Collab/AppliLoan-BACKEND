<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequests;
use App\Http\Requests\RegisterRequests;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
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


class AuthController extends Controller
{
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
                'is_member' => false,
            ]);

            // Generate OTP and send email
            $otp = $user->generateOtp();
            Mail::to($user->email)->send(new VerificationCodeMail($otp, 'signup'));

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully. Please check your email for a verification code.',
                'user' => $user->only(['id', 'email', 'role']),
                'requires_verification' => true,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    
    }

    public function verifyEmail(VerifyOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->verifyOtp($request->otp)) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
                'success' => false,
            ], 400);
        }

        if ($request->type === 'signup') {
            $user->email_verified_at = Carbon::now();
            $user->is_verified = true;
            $user->clearOtp();

            return response()->json([
                'message' => 'Email verified successfully. You can now sign in.',
                'success' => true,
                'verified' => true,
            ], 200);
        }

        if ($request->type === 'forgot-password') {
            // Don't clear OTP yet, will be cleared after password reset
            return response()->json([
                'message' => 'Verification code confirmed. You can now reset your password.',
                'success' => true,
                'verified' => true,
                'reset_token' => $request->otp, // Use OTP as temporary reset token
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid verification type.',
            'success' => false,
        ], 400);
    }

    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'type' => 'required|in:signup,forgot-password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false,
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($request->type === 'signup' && $user->is_verified) {
            return response()->json([
                'message' => 'Email is already verified.',
                'success' => false,
            ], 400);
        }

        $otp = $user->generateOtp();
        Mail::to($user->email)->send(new VerificationCodeMail($otp, $request->type));

        return response()->json([
            'message' => 'A new verification code has been sent to your email.',
            'success' => true,
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->verifyOtp($request->otp)) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
                'success' => false,
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->clearOtp();

        return response()->json([
            'message' => 'Password reset successfully. You can now sign in with your new password.',
            'success' => true,
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        $otp = $user->generateOtp();

        Mail::to($user->email)->send(new VerificationCodeMail($otp, 'forgot-password'));

        return response()->json([
            'message' => 'A verification code has been sent to your email for password reset.',
            'success' => true,
        ], 200);
    }

    public function login(LoginRequests $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials',
                'success' => false,
            ], 401);
        }

        $user = User::where('email', $request->input('email'))->first();

        // Check if user is verified for members
        if ($user->role->value === 'member' && !$user->is_verified) {
            return response()->json([
                'message' => 'Please verify your email before signing in',
                'success' => false,
                'requires_verification' => true,
            ], 403);
        }

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
            'success' => true,
        ], 200);
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

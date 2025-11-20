<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        // Automatically make all registered users members
        Member::create([
            'user_id' => $user->id,
        ]);

        event(new Registered($user));

        // Send email verification notification (queued)
        // Wrap in try-catch to ensure registration doesn't fail if email queue fails
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            // Log the error but don't fail registration
            \Log::warning('Failed to queue email verification notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        Auth::login($user);

        $user->load('member');
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'is_member' => $user->isMember(),
            ],
            'token' => $token,
            'message' => 'Registration successful! Please check your email for the verification link. The email may take a few moments to arrive.',
        ], 201);
    }
}

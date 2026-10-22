<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReferralVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'This email address is already registered. Please sign in instead.',
            'terms.accepted' => 'You must accept the Terms of Service and Risk Disclosure to create an account.',
        ]);

        $email = strtolower(trim($validated['email']));
        $isOwner = in_array($email, ['nouchansokhea0@gmail.com', 'admin@chartsense.ai'], true);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => $isOwner ? 'admin' : 'user',
            'account_status' => 'active',
            'email_verified_at' => now(), // Default instant verified for seamless referral onboarding
        ]);

        // Initialize user referral verification record
        ReferralVerification::create([
            'user_id' => $user->id,
            'status' => $isOwner ? 'VERIFIED' : 'NOT_STARTED',
            'verification_method' => $isOwner ? 'admin_granted' : 'manual_review',
        ]);

        // Audit Log
        AuditLog::log('user_registered', 'User', (string)$user->id, [
            'email' => $user->email,
            'name' => $user->name,
        ], $user->id);

        Auth::login($user);

        if ($isOwner) {
            return redirect()->route('admin.dashboard')->with('success', 'Welcome Administrator! Your account is active with full access.');
        }

        return redirect()->route('referral-access')->with('success', 'Account created! Complete your Exness referral verification below to unlock the AI Chart Scanner.');
    }
}

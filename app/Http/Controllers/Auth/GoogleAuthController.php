<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReferralVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page or Google account chooser.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        // If real Google OAuth credentials are configured in .env, redirect to official Google accounts page
        if (!empty($clientId) && !empty($clientSecret)) {
            try {
                return Socialite::driver('google')->redirect();
            } catch (\Throwable $e) {
                Log::error('Google OAuth Redirect Error: ' . $e->getMessage());
                return redirect()->route('login')->with('error', 'Could not initiate Google authentication. Please try again or sign in with email.');
            }
        }

        // Otherwise, open the authentic Google Account Chooser screen
        return redirect()->route('auth.google.select');
    }

    /**
     * Display the Google Account Chooser screen.
     */
    public function selectAccount(Request $request): View
    {
        $accounts = User::whereNotNull('email')
            ->orderByDesc('id')
            ->take(3)
            ->get(['name', 'email', 'avatar']);

        // Ensure default user Nou Chansokhea (nouchansokhea0@gmail.com) is present
        $hasNou = $accounts->contains('email', 'nouchansokhea0@gmail.com');
        if (!$hasNou) {
            $accounts->prepend((object)[
                'name' => 'Nou Chansokhea',
                'email' => 'nouchansokhea0@gmail.com',
                'avatar' => null,
            ]);
        }

        return view('auth.google-select-account', compact('accounts'));
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth Callback Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Google authentication failed or was cancelled. Please try again.');
        }

        return $this->loginOrCreateUser($googleUser->getId(), $googleUser->getEmail(), $googleUser->getName(), $googleUser->getAvatar(), $request);
    }

    /**
     * Development / Local Mock sign-in with Google email for immediate testing when Google Cloud keys are pending.
     */
    public function devMockLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $email = strtolower(trim($validated['email']));
        $name = !empty($validated['name']) ? trim($validated['name']) : explode('@', $email)[0];
        $pseudoGoogleId = 'mock_google_' . md5($email);
        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0284c7&color=fff';

        return $this->loginOrCreateUser($pseudoGoogleId, $email, $name, $avatar, $request);
    }

    /**
     * Internal handler to authenticate or register a user by Google profile.
     */
    protected function loginOrCreateUser(string $googleId, string $email, ?string $name, ?string $avatar, Request $request): RedirectResponse
    {
        $email = strtolower(trim($email));

        // 1. Try finding existing user by google_id OR by email
        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if ($user) {
            // Update Google metadata if missing
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $googleId;
            }
            if (!empty($avatar) && empty($user->avatar)) {
                $updates['avatar'] = $avatar;
            }
            if (empty($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }

            // Check if suspended
            if ($user->isSuspended()) {
                return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
            }

            // Promote owner to admin if applicable
            $isOwner = in_array(strtolower($user->email), ['nouchansokhea0@gmail.com', 'admin@chartsense.ai'], true);
            if ($isOwner && $user->role !== 'admin') {
                $user->update(['role' => 'admin']);
                ReferralVerification::updateOrCreate(
                    ['user_id' => $user->id],
                    ['status' => 'VERIFIED', 'verification_method' => 'admin_granted']
                );
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            AuditLog::log('user_logged_in_google', 'User', (string)$user->id, [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back, ' . $user->name . '!');
            }

            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . $user->name . '!');
        }

        // 2. Register brand new user via Google
        $isOwner = in_array(strtolower($email), ['nouchansokhea0@gmail.com', 'admin@chartsense.ai'], true);
        $user = User::create([
            'name' => !empty($name) ? $name : explode('@', $email)[0],
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $avatar,
            'password' => null, // OAuth-only user (no password required)
            'role' => $isOwner ? 'admin' : 'user',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Initialize referral verification record
        ReferralVerification::create([
            'user_id' => $user->id,
            'status' => $isOwner ? 'VERIFIED' : 'NOT_STARTED',
            'verification_method' => $isOwner ? 'admin_granted' : 'manual_review',
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLog::log('user_registered_google', 'User', (string)$user->id, [
            'email' => $user->email,
            'name' => $user->name,
            'google_id' => $googleId,
            'ip' => $request->ip(),
        ]);

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Welcome Administrator! Your account has been initialized.');
        }

        return redirect()->route('dashboard')->with('success', 'Account created successfully with Google! Welcome to ChartSense AI.');
    }

    /**
     * Authenticate or register a user from Firebase Google authentication token.
     */
    public function handleFirebaseSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'uid' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $name = !empty($validated['name']) ? trim($validated['name']) : explode('@', $email)[0];
        $uid = $validated['uid'];
        $avatar = $validated['avatar'] ?? null;

        $user = User::where('google_id', $uid)->orWhere('email', $email)->first();

        if ($user) {
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $uid;
            }
            if (!empty($avatar) && empty($user->avatar)) {
                $updates['avatar'] = $avatar;
            }
            if (empty($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }

            if ($user->isSuspended()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Your account has been suspended. Please contact support.'
                ], 403);
            }

            $isOwner = in_array($email, ['nouchansokhea0@gmail.com', 'admin@chartsense.ai'], true);
            if ($isOwner && $user->role !== 'admin') {
                $user->update(['role' => 'admin']);
                ReferralVerification::updateOrCreate(
                    ['user_id' => $user->id],
                    ['status' => 'VERIFIED', 'verification_method' => 'admin_granted']
                );
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            AuditLog::log('user_logged_in_firebase_google', 'User', (string)$user->id, [
                'email' => $user->email,
                'uid' => $uid,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => $user->isAdmin() ? route('admin.dashboard') : route('dashboard'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
        }

        // New registration via Firebase Google
        $isOwner = in_array($email, ['nouchansokhea0@gmail.com', 'admin@chartsense.ai'], true);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $uid,
            'avatar' => $avatar,
            'password' => null,
            'role' => $isOwner ? 'admin' : 'user',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        ReferralVerification::create([
            'user_id' => $user->id,
            'status' => $isOwner ? 'VERIFIED' : 'NOT_STARTED',
            'verification_method' => $isOwner ? 'admin_granted' : 'manual_review',
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLog::log('user_registered_firebase_google', 'User', (string)$user->id, [
            'email' => $user->email,
            'name' => $user->name,
            'uid' => $uid,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => $user->isAdmin() ? route('admin.dashboard') : route('dashboard'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}

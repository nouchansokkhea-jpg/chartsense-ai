<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show profile settings.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        return view('dashboard.profile', compact('user'));
    }

    /**
     * Update basic profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $oldEmail = $user->email;
        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->save();

        AuditLog::log('profile_updated', 'User', (string)$user->id, [
            'name' => $user->name,
            'email_changed' => $oldEmail !== $user->email,
        ], $user->id);

        return redirect()->route('profile.edit')->with('success', 'Profile information updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        AuditLog::log('password_changed', 'User', (string)$user->id, [], $user->id);

        return redirect()->route('profile.edit')->with('success', 'Password updated successfully.');
    }
}

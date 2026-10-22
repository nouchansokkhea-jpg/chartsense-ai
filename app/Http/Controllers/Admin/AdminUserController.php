<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Display paginated user list.
     */
    public function index(Request $request): View
    {
        $query = User::with(['latestReferralVerification'])->latest('id');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                  ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Suspend user account.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot suspend your own administrative account.');
        }

        $user->account_status = 'suspended';
        $user->save();

        AuditLog::log('user_suspended', 'User', (string)$user->id, [
            'email' => $user->email,
        ], $user->id, $request->user()->id);

        return redirect()->back()->with('warning', "User [{$user->email}] has been suspended.");
    }

    /**
     * Restore user account to active status.
     */
    public function restore(Request $request, User $user): RedirectResponse
    {
        $user->account_status = 'active';
        $user->save();

        AuditLog::log('user_restored', 'User', (string)$user->id, [
            'email' => $user->email,
        ], $user->id, $request->user()->id);

        return redirect()->back()->with('success', "User [{$user->email}] has been restored to active status.");
    }

    /**
     * Change user role (user <-> admin).
     */
    public function toggleRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot change your own administrative role.');
        }

        $newRole = $user->role === 'admin' ? 'user' : 'admin';
        $user->role = $newRole;
        $user->save();

        AuditLog::log('user_role_changed', 'User', (string)$user->id, [
            'new_role' => $newRole,
        ], $user->id, $request->user()->id);

        return redirect()->back()->with('success', "User [{$user->email}] role updated to [{$newRole}].");
    }
}

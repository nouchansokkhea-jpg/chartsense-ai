<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\AdminScanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Referral\ReferralAccessController;
use App\Http\Controllers\Scanner\ScannerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ChartSense AI
|--------------------------------------------------------------------------
|
| Public Routes:
| GET /                  -> Landing page
| GET /terms             -> Terms of Service
| GET /privacy           -> Privacy Policy
| GET /risk-disclosure   -> Financial Risk Disclosure
|
*/

Route::get('/', function () {
    return view('landing.index');
})->name('home');

Route::get('/terms', function () {
    return view('legal.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('legal.privacy');
})->name('privacy');

Route::get('/risk-disclosure', function () {
    return view('legal.risk-disclosure');
})->name('risk-disclosure');

Route::get('/system/bootstrap-admin', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

        $owner = \App\Models\User::updateOrCreate(
            ['email' => 'nouchansokhea0@gmail.com'],
            [
                'name' => 'Nou Chansokhea',
                'password' => \Illuminate\Support\Facades\Hash::make('Password@123456'),
                'role' => 'admin',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $owner->update(['role' => 'admin', 'account_status' => 'active']);

        \App\Models\ReferralVerification::updateOrCreate(
            ['user_id' => $owner->id],
            [
                'status' => 'VERIFIED',
                'verification_method' => 'admin_granted',
                'exness_account_id' => 'ADMIN-OWNER',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $owner->id,
            ]
        );

        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@chartsense.ai'],
            [
                'name' => 'ChartSense Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Password@123456'),
                'role' => 'admin',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Admin accounts initialized successfully!',
            'users' => \App\Models\User::all(['id', 'email', 'role']),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:15,1');

    // Google & Firebase Authentication Routes
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/select-account', [GoogleAuthController::class, 'selectAccount'])->name('auth.google.select');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::post('/auth/google/dev-mock', [GoogleAuthController::class, 'devMockLogin'])->name('auth.google.dev-mock');
    Route::post('/auth/firebase/session', [GoogleAuthController::class, 'handleFirebaseSession'])->name('auth.firebase.session')->middleware('throttle:20,1');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Active Account)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Settings
    Route::get('/settings/profile', [ProfileController::class, 'index'])->name('profile.edit');
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Exness Referral Access Gate
    Route::get('/referral-access', [ReferralAccessController::class, 'index'])->name('referral-access');
    Route::post('/referral-access/submit', [ReferralAccessController::class, 'submit'])->name('referral-access.submit')->middleware('throttle:10,1');
    Route::get('/referral-access/click', [ReferralAccessController::class, 'recordLinkClick'])->name('referral-access.click');

    /*
    |--------------------------------------------------------------------------
    | Protected Scanner Routes (Requires Referral Verified Status)
    |--------------------------------------------------------------------------
    */
    Route::middleware('referral.verified')->group(function () {
        Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
        Route::post('/scanner', [ScannerController::class, 'store'])->name('scans.store')->middleware('throttle:15,1');
        Route::post('/scans', [ScannerController::class, 'store'])->name('scanner.store')->middleware('throttle:15,1');

        Route::get('/scans', [ScannerController::class, 'history'])->name('scans.index');
        Route::get('/scans/{scan}', [ScannerController::class, 'show'])->name('scans.show');
        Route::delete('/scans/{scan}', [ScannerController::class, 'destroy'])->name('scans.destroy');
        Route::get('/scans/{scan}/pdf', [ScannerController::class, 'exportPdf'])->name('scans.pdf');
        Route::get('/scans/{scan}/csv', [ScannerController::class, 'exportCsv'])->name('scans.csv');
        Route::get('/scans/{scan}/screenshot', [ScannerController::class, 'screenshot'])->name('scans.screenshot');
    });

    /*
    |--------------------------------------------------------------------------
    | Administrator Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Referral Verification Management
        Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals');
        Route::post('/referrals/{verification}/approve', [AdminReferralController::class, 'approve'])->name('referrals.approve');
        Route::post('/referrals/{verification}/reject', [AdminReferralController::class, 'reject'])->name('referrals.reject');
        Route::post('/referrals/{verification}/suspend', [AdminReferralController::class, 'suspend'])->name('referrals.suspend');
        Route::get('/referrals/evidence/{evidence}', [AdminReferralController::class, 'downloadEvidence'])->name('referrals.evidence');

        // User Management
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::post('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggle-role');

        // Scans Monitor
        Route::get('/scans', [AdminScanController::class, 'index'])->name('scans');

        // AI & System Settings
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

        // System Audit Logs
        Route::get('/audit-logs', [AdminSettingController::class, 'auditLogs'])->name('audit-logs');
    });
});

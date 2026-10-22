@extends('layouts.app')

@section('title', 'Profile Settings - ChartSense AI')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header -->
    <div class="border-b border-slate-800 pb-4">
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Profile &amp; Security Settings</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your account information, security credentials, and referral standing.</p>
    </div>

    <!-- Referral Access Status Card -->
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
        <div class="space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Exness Referral Access</span>
            <div class="flex items-center space-x-2">
                @php $status = $user->referralStatus(); @endphp
                @if($status === 'VERIFIED')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-400 border border-emerald-800">
                        ✓ Verified &bull; Scanner Active
                    </span>
                @elseif($status === 'PENDING_VERIFICATION')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950 text-amber-400 border border-amber-800">
                        ⏳ Verification Pending Review
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-400 border border-rose-800">
                        🔒 Scanner Locked
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('referral-access') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
            Manage Referral &rarr;
        </a>
    </div>

    <!-- Personal Info Form -->
    <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
        <div class="border-b border-slate-800/80 pb-4">
            <h2 class="text-lg font-bold text-white">Personal Information</h2>
            <p class="text-xs text-slate-400 mt-0.5">Update your display name and email address.</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Full Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('name') border-rose-500 @enderror">
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('email') border-rose-500 @enderror">
                @error('email')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Password Security Form -->
    <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
        <div class="border-b border-slate-800/80 pb-4">
            <h2 class="text-lg font-bold text-white">Security &amp; Password</h2>
            <p class="text-xs text-slate-400 mt-0.5">Ensure your account is using a strong, unique password.</p>
        </div>

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Current Password</label>
                <input id="current_password" name="current_password" type="password" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('current_password') border-rose-500 @enderror">
                @error('current_password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">New Password</label>
                <input id="password" name="password" type="password" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('password') border-rose-500 @enderror">
                @error('password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
            </div>

            <div class="pt-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

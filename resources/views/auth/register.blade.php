@extends('layouts.app')

@section('title', 'Create Free Account - ChartSense AI')

@section('content')
<div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative">
    <!-- Ambient glow -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[400px] bg-cyan-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center space-y-2">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/80 text-xs text-cyan-300">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
            <span>100% Free &bull; Referral Access</span>
        </div>
        <h2 class="text-3xl font-extrabold text-white tracking-tight">Create your account</h2>
        <p class="text-xs text-slate-400">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-cyan-400 hover:text-cyan-300 transition">Sign in</a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="p-8 sm:p-10 rounded-2xl bg-slate-900/80 border border-slate-800/90 shadow-2xl backdrop-blur-xl space-y-5">
            <!-- No Billing Reminder -->
            <div class="p-3.5 rounded-xl bg-slate-800/50 border border-slate-700/60 flex items-start space-x-3 text-xs text-slate-300">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                <span>Zero payments or subscriptions. Scanner access is sponsored by our official Exness partner verification.</span>
            </div>

            <!-- Get Started with Google Button (Firebase Powered) -->
            <div>
                <button type="button" onclick="if(window.loginWithFirebaseGoogle){ window.loginWithFirebaseGoogle(this); } else { window.location.href='{{ route('auth.google') }}'; }" style="background-color: #ffffff !important; color: #0f172a !important;" class="w-full flex items-center justify-center space-x-3 py-3 px-4 rounded-xl font-bold text-sm shadow-lg shadow-white/10 hover:opacity-95 transition duration-150 border border-slate-200 cursor-pointer">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span style="color: #0f172a !important;">Get started with Google</span>
                </button>
            </div>


            <!-- Sleek Divider -->
            <div class="relative flex items-center justify-center py-1">
                <div class="w-full border-t border-slate-800"></div>
                <span class="absolute px-3 bg-slate-900 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">or create with email</span>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Trader" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('name') border-rose-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="trader@example.com" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('email') border-rose-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="At least 8 characters" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition @error('password') border-rose-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Re-enter your password" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                </div>

                <!-- Terms & Risk Acceptance -->
                <div class="pt-2">
                    <label class="flex items-start space-x-2.5 cursor-pointer">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required class="mt-1 rounded bg-slate-950 border-slate-700 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-slate-900">
                        <span class="text-xs text-slate-400 leading-snug">
                            I accept the <a href="{{ url('/terms') }}" target="_blank" class="text-cyan-400 underline">Terms of Service</a>, <a href="{{ url('/privacy') }}" target="_blank" class="text-cyan-400 underline">Privacy Policy</a>, and understand the <a href="{{ url('/risk-disclosure') }}" target="_blank" class="text-amber-400 underline">Financial Risk Disclosure</a>.
                        </span>
                    </label>
                    @error('terms')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-xl shadow-cyan-500/25 transition duration-150">
                        Create Free Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

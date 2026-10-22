<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - Google Accounts</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:400,500,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex flex-col justify-between bg-[#F0F4F9] text-[#1f1f1f] antialiased" x-data="{ useAnother: false, customEmail: '', customName: '' }">
    
    <div class="flex-grow flex items-center justify-center p-4 sm:p-6">
        <!-- Google Card Container -->
        <div class="w-full max-w-[450px] bg-white rounded-[28px] p-8 sm:p-10 shadow-sm border border-slate-200/80 transition duration-200">
            
            <!-- Google Logo -->
            <div class="flex justify-center mb-4">
                <svg class="w-7 h-7" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
            </div>

            <!-- Title & Subtitle -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-normal text-[#1f1f1f] tracking-tight">Choose an account</h1>
                <p class="text-sm text-[#444746] mt-1.5">
                    to continue to <span class="font-medium text-[#0b57d0]">ChartSense AI</span>
                </p>
            </div>

            <!-- Accounts List -->
            <div class="divide-y divide-slate-100 border-y border-slate-100 mb-6">
                @foreach($accounts as $acc)
                    <form method="POST" action="{{ route('auth.google.dev-mock') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="email" value="{{ $acc->email }}">
                        <input type="hidden" name="name" value="{{ $acc->name ?? explode('@', $acc->email)[0] }}">
                        <button type="submit" class="w-full flex items-center space-x-3.5 py-3.5 px-3 rounded-xl hover:bg-[#F2F6FC] transition duration-150 text-left group">
                            <!-- Avatar Circle -->
                            <div class="w-9 h-9 rounded-full bg-[#1A73E8] text-white flex items-center justify-center font-medium text-sm shrink-0 shadow-sm">
                                {{ strtoupper(substr($acc->name ?? $acc->email, 0, 1)) }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <div class="text-sm font-medium text-[#1f1f1f] truncate group-hover:text-[#0b57d0] transition">
                                    {{ $acc->name ?? 'Google Trader' }}
                                </div>
                                <div class="text-xs text-[#5E5E5E] truncate">
                                    {{ $acc->email }}
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#0b57d0] transition shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </form>
                @endforeach

                <!-- Use Another Account Option -->
                <div>
                    <button type="button" @click="useAnother = !useAnother" class="w-full flex items-center space-x-3.5 py-3.5 px-3 rounded-xl hover:bg-[#F2F6FC] transition duration-150 text-left group">
                        <div class="w-9 h-9 rounded-full bg-slate-100 text-[#444746] flex items-center justify-center shrink-0 border border-slate-200">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </div>
                        <div class="flex-grow text-sm font-medium text-[#1f1f1f] group-hover:text-[#0b57d0] transition">
                            Use another account
                        </div>
                    </button>

                    <!-- Expandable Input for Custom Account -->
                    <div x-show="useAnother" x-cloak class="p-3 bg-slate-50 rounded-xl mb-2 mt-1 space-y-2 border border-slate-200">
                        <form method="POST" action="{{ route('auth.google.dev-mock') }}" class="space-y-2.5">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Enter your Google Email</label>
                                <input type="email" name="email" required placeholder="youremail@gmail.com" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:border-[#0b57d0] focus:ring-1 focus:ring-[#0b57d0]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Your Full Name (optional)</label>
                                <input type="text" name="name" placeholder="John Trader" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:border-[#0b57d0] focus:ring-1 focus:ring-[#0b57d0]">
                            </div>
                            <div class="flex justify-end pt-1">
                                <button type="submit" class="px-5 py-2 rounded-full bg-[#0b57d0] hover:bg-[#0842a0] text-white text-xs font-semibold shadow-sm transition">
                                    Next &rarr;
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Google Consent Notice -->
            <p class="text-xs text-[#5E5E5E] leading-relaxed mb-6">
                To continue, Google will share your name, email address, language preference, and profile picture with <strong class="text-slate-800">ChartSense AI</strong>. Before using this app, you can review ChartSense AI's
                <a href="{{ url('/privacy') }}" class="text-[#0b57d0] hover:underline">privacy policy</a> and
                <a href="{{ url('/terms') }}" class="text-[#0b57d0] hover:underline">terms of service</a>.
            </p>

            <!-- Back to App Link -->
            <div class="text-center pt-2 border-t border-slate-100">
                <a href="{{ route('login') }}" class="text-xs font-medium text-[#0b57d0] hover:underline">
                    &larr; Cancel and return to ChartSense AI
                </a>
            </div>
        </div>
    </div>

    <!-- Official Google Bottom Footer -->
    <div class="w-full max-w-[450px] mx-auto px-4 py-3 flex items-center justify-between text-xs text-[#5E5E5E]">
        <div>
            <span>English (United States)</span>
        </div>
        <div class="flex space-x-4">
            <a href="{{ url('/privacy') }}" class="hover:text-slate-800">Help</a>
            <a href="{{ url('/privacy') }}" class="hover:text-slate-800">Privacy</a>
            <a href="{{ url('/terms') }}" class="hover:text-slate-800">Terms</a>
        </div>
    </div>

</body>
</html>

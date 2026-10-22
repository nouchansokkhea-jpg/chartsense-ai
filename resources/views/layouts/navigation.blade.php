<nav x-data="{ mobileOpen: false }" class="sticky top-0 z-50 border-b border-slate-800/80 bg-[#0B0F19]/90 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Left: Logo & Brand -->
            <div class="flex items-center space-x-3">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <!-- Custom ChartSense AI SVG Icon -->
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 via-blue-600 to-indigo-500 p-0.5 shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition duration-300">
                        <div class="w-full h-full bg-[#0B0F19] rounded-[10px] flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400 group-hover:scale-110 transition duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <!-- Candlestick Bar 1 -->
                                <path d="M6 3v4M6 17v4M4 7h4v10H4z" />
                                <!-- Candlestick Bar 2 -->
                                <path d="M18 5v2M18 19v2M16 7h4v12h-4z" />
                                <!-- AI Scanner Lens / Wave -->
                                <path d="M10 9l3 3-2 2 3 3" stroke-width="2.5" class="text-emerald-400" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight text-white font-sans">ChartSense <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400">AI</span></span>
                        <span class="hidden sm:inline-block ml-2 px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase rounded-full bg-cyan-950/80 text-cyan-400 border border-cyan-800/60">Scanner</span>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <div class="hidden md:flex items-center space-x-1 pl-6">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('/') ? 'text-cyan-400 bg-slate-800/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/30' }} transition">
                        Home
                    </a>
                    <a href="{{ url('/#features') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/30 transition">
                        Features
                    </a>
                    <a href="{{ url('/#how-it-works') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/30 transition">
                        How It Works
                    </a>
                    <a href="{{ url('/referral-access') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('referral-access*') ? 'text-cyan-400 bg-slate-800/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/30' }} transition flex items-center space-x-1.5">
                        <span>Exness Access</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    </a>
                    <a href="{{ url('/scanner') }}" class="px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('scanner*') ? 'text-cyan-400 bg-slate-800/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/30' }} transition flex items-center space-x-1.5">
                        <span>AI Scanner</span>
                        @auth
                            @if(auth()->user()->canAccessScanner())
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-emerald-950 text-emerald-400 border border-emerald-800">UNLOCKED</span>
                            @else
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-amber-950 text-amber-400 border border-amber-800">LOCKED</span>
                            @endif
                        @else
                            <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        @endauth
                    </a>
                </div>
            </div>

            <!-- Right: Action Buttons / Auth -->
            <div class="hidden md:flex items-center space-x-3">
                <!-- Join Exness Quick Action (Official link) -->
                <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/30 hover:bg-amber-500/20 transition">
                    <span>Join Exness</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                </a>

                @auth
                    <!-- Authenticated Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="flex items-center space-x-2.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/60 hover:bg-slate-800 text-slate-200 text-sm font-medium transition">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400 transition transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-56 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl py-1.5 z-50 text-slate-200 text-sm divide-y divide-slate-800/80" style="display: none;">
                            <div class="px-4 py-2">
                                <p class="text-xs text-slate-400">Signed in as</p>
                                <p class="font-semibold text-white truncate">{{ auth()->user()->email }}</p>
                                <div class="mt-1.5">
                                    @php $status = auth()->user()->referralStatus(); @endphp
                                    @if($status === 'VERIFIED')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-800">
                                            ✓ Referral Verified
                                        </span>
                                    @elseif($status === 'PENDING_VERIFICATION')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-950 text-amber-400 border border-amber-800">
                                            ⏳ Verification Pending
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-950 text-rose-400 border border-rose-800">
                                            🔒 Referral Locked
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="py-1">
                                <a href="{{ url('/dashboard') }}" class="flex items-center px-4 py-2 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-2.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                                    Dashboard
                                </a>
                                <a href="{{ url('/scanner') }}" class="flex items-center px-4 py-2 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-2.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                                    Chart Scanner
                                </a>
                                <a href="{{ url('/scans') }}" class="flex items-center px-4 py-2 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-2.5 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    Scan History
                                </a>
                                <a href="{{ url('/referral-access') }}" class="flex items-center px-4 py-2 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-2.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                    Referral Status
                                </a>
                                <a href="{{ url('/settings/profile') }}" class="flex items-center px-4 py-2 hover:bg-slate-800 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-2.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                    Profile Settings
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ url('/admin') }}" class="flex items-center px-4 py-2 hover:bg-indigo-900/40 text-indigo-300 hover:text-white transition font-medium">
                                        <svg class="w-4 h-4 mr-2.5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                        Admin Panel
                                    </a>
                                @endif
                            </div>

                            <div class="py-1">
                                <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-rose-400 hover:bg-rose-950/30 hover:text-rose-300 transition">
                                        <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <button type="button" onclick="if(window.loginWithFirebaseGoogle){ window.loginWithFirebaseGoogle(this); } else { window.location.href='{{ route('auth.google') }}'; }" title="Sign in with Google" style="background-color: #ffffff !important;" class="p-2 rounded-lg hover:opacity-90 shadow-sm border border-slate-200 transition hidden sm:inline-flex items-center justify-center cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                    </button>
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white transition">
                        Sign In
                    </a>
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-md shadow-cyan-500/25 transition">
                        Get Started
                    </a>
                @endauth
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex items-center md:hidden">
                <button @click="mobileOpen = !mobileOpen" type="button" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition">
                    <svg class="w-6 h-6" x-show="!mobileOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    <svg class="w-6 h-6" x-show="mobileOpen" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileOpen" class="md:hidden border-b border-slate-800 bg-slate-900/95 px-4 pt-2 pb-6 space-y-2" style="display: none;">
        <a href="{{ url('/') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Home</a>
        <a href="{{ url('/#features') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Features</a>
        <a href="{{ url('/#how-it-works') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">How It Works</a>
        <a href="{{ url('/referral-access') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Exness Access</a>
        <a href="{{ url('/scanner') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-cyan-400 hover:bg-slate-800">AI Scanner</a>
        <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="block px-3 py-2 rounded-lg text-base font-medium text-amber-300 hover:bg-slate-800">Join Exness Official</a>

        <div class="pt-4 border-t border-slate-800">
            @auth
                <a href="{{ url('/dashboard') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Dashboard</a>
                <a href="{{ url('/scans') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Scan History</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ url('/admin') }}" class="block px-3 py-2 rounded-lg text-base font-medium text-indigo-400 hover:bg-slate-800">Admin Area</a>
                @endif
                <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-base font-medium text-rose-400 hover:bg-rose-950/30">Sign Out</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="text-center px-4 py-2.5 rounded-lg border border-slate-700 text-slate-200 text-sm font-medium hover:bg-slate-800">Sign In</a>
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="text-center px-4 py-2.5 rounded-lg bg-cyan-500 text-slate-950 font-semibold text-sm hover:bg-cyan-400">Get Started</a>
                </div>
            @endauth
        </div>
    </div>
</nav>

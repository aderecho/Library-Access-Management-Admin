<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'UP Cebu RFID Admin Portal' }}</title>
    <link rel="icon" href="{{ asset('images/las-icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/entry-monitor.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cardholder-photos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/recent-activity-panel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/advertisements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/advertisements-media.css') }}">
    @vite(['resources/js/admin-notifications.js', 'resources/js/admin-live-updates.js', 'resources/js/advertisements.js', 'resources/js/session-expiry-warning.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <img class="brand-logo" src="{{ asset('images/las-icon.png') }}" alt="UP Cebu Library Access Management logo">
            <div class="brand-copy">
                <strong class="brand-label">Admin Portal</strong>
                <span>Library Access Management</span>
            </div>
        </div>

        <nav aria-label="Primary navigation">
            @if(auth()->user()->hasPermission('dashboard.view'))
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg></span>
                    <span>Dashboard</span>
                </a>
            @endif
            @if(auth()->user()->hasPermission('entry-monitor.view'))
                <a class="nav-link {{ request()->routeIs('admin.entry-monitor') ? 'active' : '' }}" href="{{ route('admin.entry-monitor') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 13h3l2-6 4 10 2-4h5"/><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg></span>
                    <span>Entry Monitor</span>
                </a>
            @endif
            @if(auth()->user()->hasPermission('transactions.view'))
                <a class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" href="{{ route('admin.transactions.index') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg></span>
                    <span>Transactions</span>
                </a>
            @endif
            @if(auth()->user()->hasPermission('reports.view'))
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 20V10m7 10V4m7 16v-7"/><path d="M3 20h18"/></svg></span>
                    <span>Reports</span>
                </a>
            @endif
            @if(auth()->user()->hasPermission('advertisements.view'))
                <a class="nav-link {{ request()->routeIs('admin.advertisements.*') ? 'active' : '' }}" href="{{ route('admin.advertisements.index') }}">
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 11 16-6v14L3 13v-2Z"/><path d="M7 14v5a2 2 0 0 0 2 2h2v-5"/><path d="M19 9a3 3 0 0 1 0 6"/></svg></span>
                    <span>Advertisements</span>
                </a>
            @endif

            @if(auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view') || auth()->user()->hasPermission('scanner-tokens.view') || auth()->user()->hasPermission('branches.view'))
                <div class="nav-heading">Administration</div>
                @if(auth()->user()->hasPermission('users.view'))
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.8 19c.5-3.2 2.2-5 5.2-5s4.7 1.8 5.2 5M16 7h5m-2.5-2.5v5"/></svg></span>
                        <span>User Accounts</span>
                    </a>
                @endif
                @if(auth()->user()->hasPermission('roles.view'))
                    <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 5.5 5.7v5.4c0 4.2 2.7 8.1 6.5 9.9 3.8-1.8 6.5-5.7 6.5-9.9V5.7L12 3Z"/><path d="M9 12h6m-3-3v6"/></svg></span>
                        <span>User Roles</span>
                    </a>
                @endif
                @if(auth()->user()->hasPermission('scanner-tokens.view'))
                    <a class="nav-link {{ request()->routeIs('admin.scanner-tokens.*') ? 'active' : '' }}" href="{{ route('admin.scanner-tokens.index') }}">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3H5a2 2 0 0 0-2 2v2m14-4h2a2 2 0 0 1 2 2v2M7 21H5a2 2 0 0 1-2-2v-2m14 4h2a2 2 0 0 0 2-2v-2M7 12h10"/><path d="M8 9v6m3-6v6m3-6v6m3-6v6"/></svg></span>
                        <span>Scanner Registrations</span>
                    </a>
                @endif
                @if(auth()->user()->hasPermission('branches.view'))
                    <a class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21V8l8-5 8 5v13M2 21h20"/><path d="M9 21v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/></svg></span>
                        <span>Branch Configuration</span>
                    </a>
                @endif
            @endif
        </nav>

        <form method="post" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit">
                <span class="logout-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4m4-4H9"/></svg></span>
                <span>Log out</span>
            </button>
        </form>
    </aside>

    <main class="content">
        <nav class="topbar" aria-label="Admin toolbar">
            @if(auth()->user()->hasPermission('transactions.view'))
                <form class="global-search" method="get" action="{{ route('admin.transactions.index') }}" role="search">
                    <label class="sr-only" for="global-search">Search transactions</label>
                    <input
                        id="global-search"
                        type="search"
                        name="search"
                        value="{{ request()->routeIs('admin.transactions.*') ? request('search') : '' }}"
                        placeholder="Search"
                    >
                    <button type="submit" aria-label="Search">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                        </svg>
                    </button>
                </form>
            @endif

            <div class="topbar-profile">
                <div class="topbar-profile-copy">
                    <strong>{{ auth()->user()->full_name }}</strong>
                    <span>{{ auth()->user()->assignedRoles()->pluck('name')->join(', ') ?: 'Administrator' }}</span>
                </div>
                @if(session('auth.google_avatar'))
                    <img
                        class="topbar-avatar"
                        src="{{ session('auth.google_avatar') }}"
                        alt="{{ auth()->user()->full_name }} profile photo"
                        referrerpolicy="no-referrer"
                    >
                @else
                    <div class="topbar-avatar topbar-avatar-fallback" aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                    </div>
                @endif
            </div>
        </nav>

        <header class="content-header">
            <div>
                <span class="eyebrow">University of the Philippines Cebu</span>
                <h1>{{ $heading ?? 'Admin Portal' }}</h1>
            </div>
        </header>

        @if(session('success'))
            <span
                class="sr-only"
                data-notification
                data-type="success"
                data-message="{{ session('success') }}"
                role="status"
            >{{ session('success') }}</span>
        @endif

        @if($errors->any())
            @foreach($errors->all() as $error)
                <span
                    class="sr-only"
                    data-notification
                    data-type="error"
                    data-message="{{ $error }}"
                    data-duration="7000"
                    role="alert"
                >{{ $error }}</span>
            @endforeach
        @endif

        @yield('content')
    </main>
</div>

<div
    class="session-warning"
    data-session-warning
    data-timeout-seconds="{{ max(1, (int) config('session.lifetime')) * 60 }}"
    data-warning-seconds="{{ max(5, min((int) config('session.warning_seconds'), max(1, (int) config('session.lifetime')) * 60)) }}"
    data-keep-alive-url="{{ route('session.keep-alive') }}"
    data-login-url="{{ route('login') }}"
    aria-hidden="true"
>
    <div class="session-warning-backdrop" aria-hidden="true"></div>
    <section
        class="session-warning-dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="session-warning-title"
        aria-describedby="session-warning-description"
        tabindex="-1"
    >
        <div class="session-warning-accent" aria-hidden="true"></div>
        <div class="session-warning-heading">
            <span class="session-warning-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3 5.5 5.7v5.4c0 4.2 2.7 8.1 6.5 9.9 3.8-1.8 6.5-5.7 6.5-9.9V5.7L12 3Z"/>
                    <path d="M12 7.5v4.7l3 1.7"/>
                </svg>
            </span>
            <span class="session-warning-label">Secure session</span>
        </div>

        <div class="session-warning-content">
            <div class="session-countdown" data-session-countdown-ring>
                <div class="session-countdown-inner">
                    <strong data-session-countdown>2:00</strong>
                    <span>remaining</span>
                </div>
            </div>
            <div class="session-warning-copy">
                <h2 id="session-warning-title">Your session is about to expire</h2>
                <p id="session-warning-description">For your security, you will be signed out soon. Stay signed in to continue without losing your place.</p>
                <div class="session-warning-note">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4m0 4h.01M10.3 4.9 3.2 17.2A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.8L13.7 4.9a2 2 0 0 0-3.4 0Z"/></svg>
                    Unsaved changes may be lost when the session ends.
                </div>
            </div>
        </div>

        <div class="session-warning-actions">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="session-warning-logout" type="submit">Log out now</button>
            </form>
            <button class="session-warning-continue" type="button" data-session-continue>
                <span data-session-continue-label>Stay signed in</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <p class="session-warning-status" data-session-status aria-live="polite"></p>
    </section>
</div>
</body>
</html>

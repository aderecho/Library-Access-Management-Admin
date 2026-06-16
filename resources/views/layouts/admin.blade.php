<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'UP Cebu RFID Admin Portal' }}</title>
    <link rel="icon" href="{{ asset('images/las-icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @vite(['resources/js/admin-notifications.js', 'resources/js/admin-live-updates.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <img class="brand-logo" src="{{ asset('images/las-icon.png') }}" alt="UP Cebu Library Access Management logo">
            <strong class="brand-label">Admin Portal</strong>
        </div>

        <nav>
            @if(auth()->user()->hasPermission('dashboard.view'))
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            @endif
            @if(auth()->user()->hasPermission('transactions.view'))
                <a class="{{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" href="{{ route('admin.transactions.index') }}">Transactions</a>
            @endif
            @if(auth()->user()->hasPermission('reports.view'))
                <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Reports</a>
            @endif

            @if(auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view') || auth()->user()->hasPermission('scanner-tokens.view'))
                <div class="nav-heading">Administration</div>
                @if(auth()->user()->hasPermission('users.view'))
                    <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">User Accounts</a>
                @endif
                @if(auth()->user()->hasPermission('roles.view'))
                    <a class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">User Roles</a>
                @endif
                @if(auth()->user()->hasPermission('scanner-tokens.view'))
                    <a class="{{ request()->routeIs('admin.scanner-tokens.*') ? 'active' : '' }}" href="{{ route('admin.scanner-tokens.index') }}">Scanner Registrations</a>
                @endif
            @endif
        </nav>

        <form method="post" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </aside>

    <main class="content">
        <nav class="topbar" aria-label="Admin toolbar">
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

            <div class="topbar-profile">
                <span>Hi, {{ auth()->user()->name }}</span>
                <div class="topbar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
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
</body>
</html>

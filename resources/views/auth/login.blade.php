<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UP Cebu RFID Admin Login</title>
    <link rel="icon" href="{{ asset('images/las-icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @vite('resources/js/admin-notifications.js')
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-brand">
        <img class="brand-logo login-logo" src="{{ asset('images/las-icon.png') }}" alt="UP Cebu Library Access Management logo">
        <div>
            <span class="eyebrow">University of the Philippines Cebu</span>
            <h1>RFID Admin Portal</h1>
        </div>
    </div>

    <p class="muted">Sign in to access analytics, transactions, user accounts, roles, and reports.</p>

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

    <form method="post" action="{{ route('login.store') }}" class="form-grid">
        @csrf
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
        </label>

        <button class="primary" type="submit">Sign in</button>
    </form>
</div>
</body>
</html>

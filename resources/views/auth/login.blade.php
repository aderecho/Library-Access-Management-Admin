<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a4a38">
    <title>UP Cebu Library Access System Login</title>
    <link rel="icon" href="{{ asset('images/las-icon.png') }}" type="image/png">
    <link rel="preload" href="{{ asset('images/up-cebu-campus-login.png') }}" as="image">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    @vite('resources/js/admin-notifications.js')
</head>
<body class="login-page">
<main class="login-stage">
    <div class="login-card">
        <section class="login-intro" aria-labelledby="portal-title">
            <div class="intro-copy">
                <img
                    class="intro-brand-logo"
                    src="{{ asset('images/las-icon.png') }}"
                    alt="UP Cebu Library Access System"
                >
                <h1 id="portal-title">Library Access System</h1>
            </div>

            <div class="intro-features" aria-label="System qualities">
                <span>SMART</span>
                <span>SECURE</span>
                <span>SYSTEM</span>
            </div>
        </section>

        <section class="login-access" aria-label="Administrator access">
            <div class="login-form-wrap">
                <div class="login-logos" aria-label="UP Cebu Library Access Management">
                    <img src="{{ asset('images/up-cebu-logo.png') }}" alt="University of the Philippines Cebu">
                </div>

                <header class="form-heading">
                    <h2>Welcome Back</h2>
                    <p>UP Cebu Library Access System</p>
                </header>

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

                @if(config('services.google.client_id') && config('services.google.client_secret') && config('services.google.redirect'))
                    <a class="google-sso-button" href="{{ route('login.google') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.91h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.32 2.98-7.4Z"/>
                            <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.43l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.05v2.62A10 10 0 0 0 12 22Z"/>
                            <path fill="#FBBC05" d="M6.39 13.86A6.01 6.01 0 0 1 6.08 12c0-.65.11-1.28.31-1.86V7.52H3.05A10 10 0 0 0 2 12c0 1.61.38 3.14 1.05 4.48l3.34-2.62Z"/>
                            <path fill="#EA4335" d="M12 6.01c1.47 0 2.79.5 3.82 1.5l2.88-2.87A9.67 9.67 0 0 0 12 2a10 10 0 0 0-8.95 5.52l3.34 2.62C7.18 7.77 9.39 6.01 12 6.01Z"/>
                        </svg>
                        <span>Sign in with Google</span>
                    </a>
                @endif
            </div>
        </section>
    </div>

</main>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · UP Cebu RFID</title>
    <style>
        :root { color-scheme: light; font-family: Inter, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 24px; color: #28231f; background: #f6f0e4; }
        main { width: min(100%, 480px); padding: 36px; background: #fffcf6; border: 1px solid #ded5c8; border-radius: 20px; box-shadow: 0 24px 70px rgba(54, 29, 20, .18); text-align: center; }
        .icon { width: 56px; height: 56px; display: grid; place-items: center; margin: 0 auto 20px; color: white; background: {{ $authorized ? '#19714d' : '#a52a38' }}; border-radius: 16px; font-size: 26px; }
        h1 { margin: 0; color: #500914; font-family: Georgia, serif; font-size: 30px; font-weight: 500; }
        p { margin: 14px 0 0; color: #746d64; line-height: 1.6; }
    </style>
</head>
<body>
    <main>
        <div class="icon" aria-hidden="true">{{ $authorized ? '✓' : '!' }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>

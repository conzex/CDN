<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CDN Manager') }}</title>
    <link rel="icon" href="https://cdn.conzex.com/bg/dc.jpg" type="image/jpeg">
    <!-- Inline script to prevent FOWT (Flash of Wrong Theme) -->
    <script>(function(){var t=localStorage.getItem('cdn-theme')||'{{ auth()->check() ? auth()->user()->theme : "light" }}';if(t==='dark')document.documentElement.classList.add('dark');})();</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class'
      }
    </script>
    <link rel="stylesheet" href="{{ asset('css/onedrive.css') }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[var(--bg-app)] text-[var(--text-primary)] font-sans antialiased min-h-screen">
    @yield('content')
    <script src="{{ asset('js/filemanager.js') }}"></script>
    @stack('scripts')
</body>
</html>

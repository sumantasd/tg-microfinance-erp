<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>@yield('title', 'TG Microfinance ERP - Empowering Financial Independence')</title>
    <meta name="description" content="@yield('meta_description', 'Leading Enterprise Microfinance ERP solution offering accessible loans, savings accounts, and multi-branch financial services.')">
    <meta name="keywords" content="microfinance, ERP, small business loans, savings schemes, microcredit, financial portal">
    <meta property="og:title" content="@yield('title', 'TG Microfinance ERP')">
    <meta property="og:description" content="@yield('meta_description', 'Enterprise Microfinance ERP & Corporate Web Portal')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @if(isset($settings) && $settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif

    <!-- Vite Asset Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="public-body d-flex flex-column min-vh-100">

    <!-- Public Shared Navigation Header -->
    <x-layouts.public-header />

    <!-- Main Page Content Body -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Public Shared Footer -->
    <x-layouts.public-footer />

    @stack('scripts')
</body>
</html>

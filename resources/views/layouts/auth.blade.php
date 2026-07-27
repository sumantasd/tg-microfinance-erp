<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Staff Portal - TG Microfinance ERP')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Asset Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-dark min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0d6efd 100%);">

    <div class="container" style="max-width: 440px;">
        <!-- Brand Header with Dynamic Logo -->
        @php
            $settings = \App\Models\WebsiteSetting::first();
            $companyName = $settings->company_name ?? 'TG Microfinance';
            $companyLogo = $settings->logo_url ?? null;
        @endphp
        <div class="text-center mb-4">
            <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-white">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="img-fluid" style="max-height: 48px; max-width: 180px; object-fit: contain;">
                @else
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center shadow-lg" style="width: 46px; height: 46px;">
                        <i class="bi bi-bank2 fs-4"></i>
                    </div>
                    <span class="fs-4 fw-bold tracking-tight text-white">{{ $companyName }}</span>
                @endif
            </a>
            <span class="d-block small text-light opacity-75 mt-1">Enterprise Staff & Portal Security</span>
        </div>

        <!-- Auth Card Canvas -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
            <div class="card-body p-4 p-md-5">
                @yield('content')
            </div>
        </div>

        <!-- Auth Footer Note -->
        <div class="text-center mt-4">
            <small class="text-light opacity-75">&copy; {{ date('Y') }} TG Microfinance ERP. Authorized Access Only.</small>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

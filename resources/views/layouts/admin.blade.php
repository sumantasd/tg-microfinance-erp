<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a1e36">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Grihalaxmi ERP">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Admin Panel - TG Microfinance ERP')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Vite Asset Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $t = \App\Services\SystemThemeService::getTheme();
    @endphp

    <!-- Dynamic Database-Driven Theme Customization CSS -->
    <style id="system-dynamic-theme-css">
    :root {
        --theme-primary: {{ $t->primary_color }};
        --theme-secondary: {{ $t->secondary_color }};
        --theme-accent: {{ $t->accent_color }};
        --theme-sidebar: {{ $t->sidebar_color }};
        --theme-sidebar-text: {{ $t->sidebar_text_color }};
        --theme-sidebar-active: {{ $t->sidebar_active_color }};
        --theme-header: {{ $t->header_color }};
        --theme-header-text: {{ $t->header_text_color }};
        --theme-background: {{ $t->body_background_color }};
        --theme-card: {{ $t->card_background_color }};
        --theme-border: {{ $t->border_color }};
        --theme-radius: {{ $t->button_radius }};

        /* Alias TG Core Tokens */
        --tg-primary: {{ $t->primary_color }};
        --tg-primary-hover: {{ $t->secondary_color }};
        --tg-sidebar-bg: {{ $t->sidebar_color }};
        --tg-sidebar-color: {{ $t->sidebar_text_color }};
        --tg-sidebar-active-color: {{ $t->sidebar_active_color }};
        --tg-admin-bg: {{ $t->body_background_color }};
        --tg-card-bg: {{ $t->card_background_color }};
        --tg-card-border: {{ $t->border_color }};
        --tg-card-radius: {{ $t->button_radius }};
    }
    </style>

    <!-- Critical Mobile App-Like Header, Bottom Nav & Zero-Overflow CSS -->
    <style id="mobile-fixed-nav-critical-css">
    @media (max-width: 767.98px) {
        html, body {
            max-width: 100vw !important;
            overflow-x: clip !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation;
        }

        body.admin-body {
            padding-top: calc(58px + env(safe-area-inset-top, 0px)) !important;
            padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important;
            position: relative !important;
            background-color: #f8fafc !important;
        }

        #mobile-app-fixed-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100vw !important;
            height: calc(58px + env(safe-area-inset-top, 0px)) !important;
            padding-top: env(safe-area-inset-top, 0px) !important;
            z-index: 1040 !important;
            background: rgba(255, 255, 255, 0.96) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            backdrop-filter: blur(16px) !important;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9) !important;
            box-shadow: 0 2px 10px rgba(10, 30, 54, 0.05) !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            transition: all 0.2s ease !important;
        }

        #admin-topbar {
            display: none !important;
        }

        #admin-content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100vw !important;
            overflow-x: clip !important;
            padding-top: 0 !important;
        }

        .mobile-bottom-nav {
            position: fixed !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            z-index: 1045 !important;
            background: rgba(255, 255, 255, 0.96) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            backdrop-filter: blur(20px) !important;
            border-top: 1px solid rgba(226, 232, 240, 0.95) !important;
            box-shadow: 0 -4px 20px rgba(10, 30, 54, 0.08) !important;
            height: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
            padding-bottom: env(safe-area-inset-bottom, 0px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-around !important;
        }

        .mobile-bottom-nav a,
        .mobile-bottom-nav button,
        .mobile-bottom-nav .mobile-nav-item {
            text-decoration: none !important;
            color: #64748b !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 1 0% !important;
            min-width: 0 !important;
            height: 100% !important;
            min-height: 48px !important;
            padding: 3px 0 !important;
            margin: 0 !important;
            font-family: inherit !important;
            white-space: nowrap !important;
            transition: color 0.15s ease, transform 0.12s ease !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation !important;
        }

        .mobile-bottom-nav .mobile-nav-item:active {
            transform: scale(0.92) !important;
        }

        .mobile-bottom-nav a:hover,
        .mobile-bottom-nav a:focus,
        .mobile-bottom-nav a:visited,
        .mobile-bottom-nav button:hover,
        .mobile-bottom-nav button:focus {
            text-decoration: none !important;
            color: #64748b !important;
        }

        .mobile-bottom-nav .nav-icon-pill {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 44px !important;
            height: 28px !important;
            border-radius: 14px !important;
            margin-bottom: 2px !important;
            transition: background-color 0.2s ease, transform 0.15s ease !important;
        }

        .mobile-bottom-nav .nav-icon-svg {
            width: 22px !important;
            height: 22px !important;
            stroke: #64748b !important;
            transition: all 0.2s ease !important;
            display: block !important;
        }

        .mobile-bottom-nav .mobile-nav-item i {
            font-size: 20px !important;
            margin-bottom: 2px !important;
            line-height: 1 !important;
            color: #64748b !important;
            transition: all 0.2s ease !important;
        }

        .mobile-bottom-nav .mobile-nav-item span {
            font-size: 10.5px !important;
            font-weight: 600 !important;
            line-height: 1.1 !important;
            color: #64748b !important;
            letter-spacing: -0.2px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            padding: 0 2px !important;
            transition: color 0.15s ease, font-weight 0.15s ease !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active {
            color: #2563eb !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active .nav-icon-pill {
            background-color: #eff6ff !important;
            color: #2563eb !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active .nav-icon-svg {
            stroke: #2563eb !important;
            transform: scale(1.05) !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active i,
        .mobile-bottom-nav .mobile-nav-item.active span {
            color: #2563eb !important;
            font-weight: 700 !important;
        }

        @media (max-width: 360px) {
            .mobile-bottom-nav .nav-icon-pill {
                width: 38px !important;
                height: 26px !important;
            }
            .mobile-bottom-nav .nav-icon-svg {
                width: 20px !important;
                height: 20px !important;
            }
            .mobile-bottom-nav .mobile-nav-item span {
                font-size: 9.5px !important;
                letter-spacing: -0.3px !important;
            }
        }
    }

    @media (min-width: 768px) {
        #mobile-app-fixed-header,
        .mobile-bottom-nav {
            display: none !important;
        }
    }
    </style>

    @stack('styles')
</head>
<body class="admin-body">

    <!-- Dedicated Fixed Mobile Top Header (< 768px) -->
    <x-layouts.admin-mobile-header />

    <!-- Shared Admin Desktop Sidebar -->
    <x-layouts.admin-sidebar />

    <!-- Full-Height Mobile Navigation Drawer -->
    <x-layouts.admin-mobile-drawer />

    <!-- Main Content Wrapper -->
    <div id="admin-content-wrapper">
        <!-- Shared Admin Topbar (Desktop) -->
        <x-layouts.admin-topbar />

        <!-- Dynamic Admin Breadcrumbs Header -->
        <x-layouts.admin-breadcrumbs :title="$title ?? 'Dashboard'" :breadcrumbs="$breadcrumbs ?? []">
            @if(isset($actions))
                <x-slot name="actions">
                    {{ $actions }}
                </x-slot>
            @endif
        </x-layouts.admin-breadcrumbs>

        <!-- Main Body Canvas -->
        <main class="admin-main-body">
            @yield('content')
        </main>

        <!-- Shared Admin Footer -->
        <x-layouts.admin-footer />
    </div>

    <!-- True Fixed 5-Button Mobile Bottom Navigation Bar (< 768px) -->
    <x-layouts.admin-mobile-bottom-nav />

    <!-- Mobile Search Modal -->
    <div class="modal fade" id="mobileSearchModal" tabindex="-1" aria-labelledby="mobileSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-top">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white p-3 border-bottom">
                    <form method="GET" action="{{ route('admin.search') }}" class="w-100 m-0 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" name="q" class="form-control bg-light border-start-0 font-monospace" placeholder="Search members, loans, applications, products..." autofocus required>
                            <button type="submit" class="btn btn-primary fw-bold">Search</button>
                        </div>
                    </form>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 bg-light">
                    <div class="small text-muted fw-bold text-uppercase mb-2" style="font-size: 0.7rem;">Quick Links</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.loan-application.create') }}" class="btn btn-sm btn-white border rounded-pill shadow-sm"><i class="bi bi-plus-circle text-primary me-1"></i>New Loan Application</a>
                        <a href="{{ route('admin.customer.create') }}" class="btn btn-sm btn-white border rounded-pill shadow-sm"><i class="bi bi-person-plus text-success me-1"></i>New Member</a>
                        <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-sm btn-white border rounded-pill shadow-sm"><i class="bi bi-cash-coin text-danger me-1"></i>EMI Collection</a>
                        <a href="{{ route('admin.product-purchase.create') }}" class="btn btn-sm btn-white border rounded-pill shadow-sm"><i class="bi bi-cart-plus text-warning me-1"></i>New Purchase</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>

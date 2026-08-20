<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - TG Microfinance ERP')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Vite Asset Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Critical Fixed Mobile Header & Bottom Nav CSS (Guarantees zero-underlines and fixed positioning across all viewports) -->
    <style id="mobile-fixed-nav-critical-css">
    @media (max-width: 767.98px) {
        html, body {
            max-width: 100vw !important;
            overflow-x: hidden !important;
        }

        body.admin-body {
            padding-top: 60px !important;
            padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px)) !important;
            position: relative !important;
        }

        #mobile-app-fixed-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100vw !important;
            height: 60px !important;
            z-index: 1040 !important;
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04) !important;
            padding: 0 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        #admin-topbar {
            display: none !important;
        }

        #admin-content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
            padding-top: 0 !important;
        }

        .mobile-bottom-nav {
            position: fixed !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            z-index: 1045 !important;
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06) !important;
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
            padding: 4px 0 !important;
            margin: 0 !important;
            font-family: inherit !important;
            white-space: nowrap !important;
        }

        .mobile-bottom-nav a:hover,
        .mobile-bottom-nav a:focus,
        .mobile-bottom-nav a:visited,
        .mobile-bottom-nav button:hover,
        .mobile-bottom-nav button:focus {
            text-decoration: none !important;
            color: #64748b !important;
        }

        .mobile-bottom-nav .mobile-nav-item i {
            font-size: 20px !important;
            margin-bottom: 2px !important;
            line-height: 1 !important;
            color: #64748b !important;
        }

        .mobile-bottom-nav .mobile-nav-item span {
            font-size: 11px !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            color: #64748b !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active {
            color: #2563eb !important;
        }

        .mobile-bottom-nav .mobile-nav-item.active i,
        .mobile-bottom-nav .mobile-nav-item.active span {
            color: #2563eb !important;
            font-weight: 700 !important;
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

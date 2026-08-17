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

    @stack('styles')
</head>
<body class="admin-body">

    <!-- Shared Admin Dark Sidebar -->
    <x-layouts.admin-sidebar />

    <!-- Main Content Wrapper -->
    <div id="admin-content-wrapper">
        <!-- Shared Admin Topbar -->
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('admin-sidebar');
        const toggler = document.getElementById('sidebar-toggler');
        if (sidebar && toggler) {
            toggler.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && e.target !== toggler) {
                    sidebar.classList.remove('show');
                }
            });
        }
    });
    </script>
    @stack('scripts')
</body>
</html>

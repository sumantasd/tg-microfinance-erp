@extends('layouts.auth')

@php
    $b = $branding ?? \App\Services\SystemBrandingService::getBranding();
@endphp

@section('title', 'Admin Login - ' . $b->company_name)

@section('content')
<style>
    .auth-page-container {
        min-height: 100vh;
        background-color: #F8F5F0;
        background-image: radial-gradient(#E8E2D8 1px, transparent 1px);
        background-size: 24px 24px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .font-serif-title {
        font-family: 'Playfair Display', Georgia, serif;
    }

    .font-caveat {
        font-family: 'Caveat', cursive;
    }

    .text-navy {
        color: #0C2340 !important;
    }

    .text-gold-accent {
        color: #C5A059 !important;
    }

    .text-gold-dark {
        color: #A67C1E !important;
    }

    /* Left Hero Card */
    .hero-card-navy {
        background: linear-gradient(135deg, rgba(12, 35, 64, 0.96) 0%, rgba(6, 18, 35, 0.92) 100%), 
                    url('{{ asset("images/login_hero_empowerment.jpg") }}');
        background-size: cover;
        background-position: center right;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(12, 35, 64, 0.25);
        color: #FFFFFF;
        position: relative;
        overflow: hidden;
        min-height: 560px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .hero-card-navy::before {
        content: '';
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        background: radial-gradient(circle at 80% 20%, rgba(197, 160, 89, 0.15) 0%, transparent 60%);
        pointer-events: none;
    }

    .hero-logo-img {
        max-height: 48px;
        max-width: 200px;
        object-fit: contain;
    }

    .feature-circle-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 1.5px solid rgba(197, 160, 89, 0.6);
        background: rgba(12, 35, 64, 0.4);
        color: #C5A059;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .glass-stats-panel {
        background: rgba(8, 24, 46, 0.75);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(197, 160, 89, 0.3);
        border-radius: 16px;
    }

    /* Right Floating Login Card */
    .login-card-white {
        background: #FFFFFF;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06), 0 5px 15px rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }

    .custom-input-group .input-group-text {
        background-color: #F8FAFC;
        border-color: #E2E8F0;
        color: #64748B;
    }

    .custom-input-group .form-control {
        background-color: #F8FAFC;
        border-color: #E2E8F0;
        color: #1E293B;
        font-size: 0.95rem;
    }

    .custom-input-group .form-control:focus {
        background-color: #FFFFFF;
        border-color: #C5A059;
        box-shadow: 0 0 0 0.25rem rgba(197, 160, 89, 0.15);
    }

    .btn-gold-action {
        background-color: #A67C1E;
        background-image: linear-gradient(135deg, #B88A28 0%, #946E18 100%);
        color: #FFFFFF;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.25 ease;
        box-shadow: 0 4px 14px rgba(166, 124, 30, 0.3);
    }

    .btn-gold-action:hover {
        background-image: linear-gradient(135deg, #C5962E 0%, #A67C1E 100%);
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(166, 124, 30, 0.4);
    }

    .divider-or {
        position: relative;
        text-align: center;
    }

    .divider-or::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0; right: 0;
        height: 1px;
        background-color: #E2E8F0;
        z-index: 1;
    }

    .divider-or-text {
        position: relative;
        z-index: 2;
        background-color: #FFFFFF;
        padding: 0 12px;
        color: #94A3B8;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Landscape Village Silhouette */
    .village-footer-silhouette {
        width: 100%;
        height: 80px;
        background-repeat: repeat-x;
        background-position: bottom center;
        opacity: 0.45;
        pointer-events: none;
    }
</style>

<div class="auth-page-container">
    
    <!-- Top Utility Navigation -->
    <div class="d-flex justify-content-end align-items-center gap-3 px-3 px-md-5 pt-3">
        <span class="text-secondary extra-small d-flex align-items-center gap-1 cursor-pointer" title="Light Mode">
            <i class="bi bi-brightness-high fs-6"></i>
        </span>
        <div class="dropdown">
            <button class="btn btn-sm btn-link text-decoration-none text-secondary dropdown-toggle extra-small fw-semibold p-0 d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                English
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                <li><a class="dropdown-item active" href="#">English</a></li>
                <li><a class="dropdown-item" href="#">Hindi (हिंदी)</a></li>
                <li><a class="dropdown-item" href="#">Bengali (বাংলা)</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container-fluid max-w-7xl mx-auto my-auto px-3 px-md-4 px-lg-5 py-3 py-md-4">
        <div class="row align-items-center g-4 g-lg-5">
            
            <!-- Left Hero Section (Desktop & Tablet) -->
            <div class="col-lg-6 col-xl-7">
                <div class="hero-card-navy p-4 p-md-5">
                    
                    <!-- Top Brand Header & Script Accent -->
                    <div>
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-3">
                                @if($b->logo_url)
                                    <img src="{{ $b->logo_url }}" alt="{{ $b->company_name }}" class="hero-logo-img">
                                @else
                                    <div class="feature-circle-icon"><i class="bi bi-bank2 fs-4"></i></div>
                                @endif
                                <div>
                                    <h4 class="font-serif-title fw-bold text-white mb-0 lh-1">{{ $b->company_name }}</h4>
                                    <span class="text-gold-accent extra-small tracking-wide uppercase">{{ $b->tagline }}</span>
                                </div>
                            </div>

                            <div class="text-end d-none d-sm-block">
                                <div class="font-caveat text-gold-accent fs-3 lh-1 fw-bold" style="transform: rotate(-3deg);">
                                    People<br>Trust<br>Progress
                                </div>
                                <div style="width: 65px; height: 2px; background: #C5A059; margin-left: auto; border-radius: 2px; transform: rotate(-3deg); margin-top: 2px;"></div>
                            </div>
                        </div>

                        <!-- Headline Section -->
                        <div class="mb-4">
                            <h1 class="font-serif-title fw-bold text-white mb-1" style="font-size: clamp(2rem, 3.5vw, 2.75rem); line-height: 1.15;">
                                Stronger Families<br>
                                <span class="text-gold-accent">Brighter Futures</span>
                            </h1>
                            <p class="text-white-50 extra-small tracking-wider uppercase mb-0">
                                Financial Inclusion &nbsp;|&nbsp; Women Empowerment &nbsp;|&nbsp; Community Growth
                            </p>
                        </div>

                        <!-- 4 Core Feature Bullet Highlights -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="feature-circle-icon"><i class="bi bi-people-fill"></i></div>
                                    <div>
                                        <h6 class="fw-semibold text-white mb-0 small">Customer Centric</h6>
                                        <span class="text-white-50 extra-small">Microfinance Solutions</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="feature-circle-icon"><i class="bi bi-graph-up-arrow"></i></div>
                                    <div>
                                        <h6 class="fw-semibold text-white mb-0 small">Transparent & Secure</h6>
                                        <span class="text-white-50 extra-small">Financial Operations</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="feature-circle-icon"><i class="bi bi-shield-check"></i></div>
                                    <div>
                                        <h6 class="fw-semibold text-white mb-0 small">Built for Sustainable</h6>
                                        <span class="text-white-50 extra-small">Community Growth</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="feature-circle-icon"><i class="bi bi-tree-fill"></i></div>
                                    <div>
                                        <h6 class="fw-semibold text-white mb-0 small">Empowering</h6>
                                        <span class="text-white-50 extra-small">A Brighter Tomorrow</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Translucent Glass Panel & Cursive Accent -->
                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2 d-none d-md-flex">
                            <div class="font-caveat text-white fs-4" style="transform: rotate(-2deg);">
                                Finance for a Better Tomorrow
                                <div style="width: 80px; height: 2px; background: #C5A059; border-radius: 2px; margin-top: -2px;"></div>
                            </div>
                        </div>

                        <div class="glass-stats-panel p-3">
                            <div class="row text-center align-items-center g-2">
                                <div class="col-4 border-end border-white border-opacity-10">
                                    <div class="text-gold-accent fw-bold fs-6 mb-0 d-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-people"></i> Happy
                                    </div>
                                    <div class="text-white-50 extra-small">Customers</div>
                                </div>
                                <div class="col-4 border-end border-white border-opacity-10">
                                    <div class="text-gold-accent fw-bold fs-6 mb-0 d-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-graph-up"></i> Growing
                                    </div>
                                    <div class="text-white-50 extra-small">Together</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-gold-accent fw-bold fs-6 mb-0 d-flex align-items-center justify-content-center gap-1">
                                        <i class="bi bi-hand-thumbs-up"></i> Stronger
                                    </div>
                                    <div class="text-white-50 extra-small">Communities</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Login Form Section -->
            <div class="col-lg-6 col-xl-5">
                <div class="login-card-white p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h2 class="font-serif-title fw-bold text-navy mb-1" style="font-size: 1.85rem;">Welcome Back</h2>
                        <p class="text-secondary small mb-0">Login to your <strong>{{ $b->company_name }}</strong> account</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger border-0 small py-2.5 px-3 mb-4 rounded-3 d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                            <div>{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" id="adminLoginForm">
                        @csrf

                        <!-- Username / Email Field -->
                        <div class="mb-3.5 mb-md-4">
                            <label class="form-label text-dark fw-semibold small mb-1.5">Email Address / Username</label>
                            <div class="input-group custom-input-group">
                                <span class="input-group-text border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="text" name="email" value="{{ old('email') }}" class="form-control border-start-0" placeholder="Enter your email or username" required autofocus autocomplete="username">
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3.5 mb-md-4">
                            <label class="form-label text-dark fw-semibold small mb-1.5">Password</label>
                            <div class="input-group custom-input-group">
                                <span class="input-group-text border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="passwordInput" class="form-control border-start-0 border-end-0" placeholder="Enter your password" required autocomplete="current-password">
                                <button type="button" class="input-group-text border-start-0 bg-light text-muted" id="togglePasswordBtn" title="Toggle password visibility">
                                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            <div class="text-end mt-1.5">
                                <a href="{{ url('/forgot-password') }}" class="text-gold-dark text-decoration-none extra-small fw-semibold">Forgot Password?</a>
                            </div>
                        </div>

                        <!-- Login Submit Button -->
                        <button type="submit" class="btn btn-gold-action w-100 py-2.5 mb-4 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                            <span>Login</span>
                        </button>

                        <!-- Divider Line -->
                        <div class="divider-or mb-4">
                            <span class="divider-or-text">OR</span>
                        </div>

                        <!-- Bottom Options Row -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                                <label class="form-check-label extra-small text-secondary user-select-none" for="remember">
                                    Remember me
                                </label>
                            </div>
                            <span class="extra-small text-navy fw-semibold d-flex align-items-center gap-1">
                                <i class="bi bi-shield-lock-fill text-navy"></i> Secure Login
                            </span>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    <!-- Bottom Rural Village Vector Silhouette & Footer Text -->
    <div>
        <!-- SVG Vector Village Silhouette matching reference photo -->
        <div class="village-footer-silhouette d-none d-md-block">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                <path d="M0 120H1440V85C1380 82 1310 95 1250 88C1180 80 1140 100 1060 92C980 84 940 70 880 78C820 86 760 98 700 88C640 78 590 92 520 84C450 76 400 95 330 88C260 81 210 96 140 90C70 84 30 98 0 85V120Z" fill="#E8DFD0" opacity="0.6"/>
                <path d="M0 120H1440V95C1390 92 1330 105 1270 98C1200 90 1160 108 1080 100C1000 92 950 82 890 89C830 96 780 106 710 98C640 90 600 102 530 95C460 88 410 104 340 98C270 92 220 105 150 98C80 91 40 104 0 95V120Z" fill="#DDD3C1" opacity="0.8"/>
            </svg>
        </div>

        <!-- Footer Text -->
        <div class="text-center pb-3 pt-1 text-secondary extra-small">
            <div>&copy; {{ date('Y') }} {{ $b->company_name }}. All rights reserved.</div>
            <div class="opacity-75 mt-0.5">Built for Financial Inclusion &nbsp;|&nbsp; Empowering Communities &nbsp;|&nbsp; A Better Tomorrow</div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.classList.toggle('bi-eye', isPassword);
                toggleIcon.classList.toggle('bi-eye-slash', !isPassword);
            });
        }
    });
</script>
@endpush
@endsection

@extends('layouts.auth')

@section('title', 'Forgot Password - TG Microfinance ERP')

@section('content')
<!-- Staff Forgot Password Form Template -->
<div class="mb-4 text-center">
    <h4 class="fw-bold text-dark mb-1">Reset Staff Password</h4>
    <p class="text-muted small mb-0">Enter your registered staff email to receive a password reset link</p>
</div>

<form action="#" method="POST">
    @csrf

    <div class="mb-4">
        <label class="form-label small fw-bold text-secondary">Staff Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="staff@tgmicrofinance.com" required autofocus>
        </div>
    </div>

    <a href="{{ url('/reset-password') }}" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 mb-3">
        <i class="bi bi-send me-1"></i> Send Password Reset Link
    </a>

    <div class="text-center">
        <a href="{{ url('/login') }}" class="small text-secondary text-decoration-none fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Staff Login
        </a>
    </div>
</form>
@endsection

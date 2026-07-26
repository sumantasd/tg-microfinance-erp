@extends('layouts.auth')

@section('title', 'Staff Login - TG Microfinance ERP')

@section('content')
<!-- Staff Login Form Template -->
<div class="mb-4 text-center">
    <h4 class="fw-bold text-dark mb-1">Staff Portal Login</h4>
    <p class="text-muted small mb-0">Sign in with your enterprise credentials</p>
</div>

@if($errors->any())
    <div class="alert alert-danger small py-2 px-3 mb-3 rounded-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
    </div>
@endif

<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label class="form-label small fw-bold text-secondary">Staff Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control bg-light border-start-0" placeholder="admin@tgmicrofinance.test" required autofocus>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small fw-bold text-secondary mb-0">Password</label>
            <a href="{{ url('/forgot-password') }}" class="small text-primary text-decoration-none fw-semibold">Forgot Password?</a>
        </div>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
        <label class="form-check-input-label small text-muted select-none" for="remember">
            Remember me on this workstation
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-box-arrow-in-right fs-5"></i>
        <span>Sign In to ERP</span>
    </button>
</form>
@endsection

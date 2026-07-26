@extends('layouts.auth')

@section('title', 'Set New Password - TG Microfinance ERP')

@section('content')
<!-- Staff Reset Password Form Template -->
<div class="mb-4 text-center">
    <h4 class="fw-bold text-dark mb-1">Set New Password</h4>
    <p class="text-muted small mb-0">Create a secure new password for your ERP staff account</p>
</div>

<form action="#" method="POST">
    @csrf

    <div class="mb-3">
        <label class="form-label small fw-bold text-secondary">New Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
            <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required autofocus>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label small fw-bold text-secondary">Confirm New Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-check2-circle"></i></span>
            <input type="password" name="password_confirmation" class="form-control bg-light border-start-0" placeholder="••••••••" required>
        </div>
    </div>

    <a href="{{ url('/login') }}" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-shield-check fs-5"></i>
        <span>Update Password & Login</span>
    </a>
</form>
@endsection

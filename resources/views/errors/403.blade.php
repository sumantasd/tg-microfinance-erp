@extends('layouts.admin')

@section('title', '403 Forbidden - Access Denied')

@section('content')
<div class="container-xl py-5 text-center">
    <div class="card border-0 shadow-lg rounded-4 p-5 mx-auto text-center" style="max-width: 580px;">
        <div class="bg-danger-subtle text-danger rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 84px; height: 84px;">
            <i class="bi bi-shield-slash fs-1"></i>
        </div>
        <h2 class="fw-bold text-dark mb-2">403 - Unauthorized Access</h2>
        <p class="text-muted lead mb-4">
            {{ $exception->getMessage() ?: 'You do not have the required role or permission privileges to access this enterprise module.' }}
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url('/admin') }}" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Return to Dashboard
            </a>
            <a href="{{ url('/contact') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-semibold">
                <i class="bi bi-envelope me-1"></i> Request Access
            </a>
        </div>
    </div>
</div>
@endsection

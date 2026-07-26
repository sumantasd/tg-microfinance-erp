@extends('layouts.admin')

@section('title', ($moduleTitle ?? 'Module') . ' - TG Microfinance ERP')

@section('content')
<!-- Developer Comment: Future Module Implementation Canvas -->
<!-- Future Module -->

<div class="container-fluid p-0">
    <x-ui.card class="p-5 text-center shadow-sm">
        <div class="bg-primary-subtle text-primary rounded-circle p-4 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="bi bi-box-seam fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">{{ $moduleTitle ?? 'ERP Module' }}</h4>
        <p class="text-muted lead mx-auto mb-4" style="max-width: 580px;">
            This admin module UI placeholder is registered for Sprint execution. Database schema, controllers, and CRUD functionality will be implemented in subsequent module sprints.
        </p>

        <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 bg-light border rounded-pill small text-secondary">
            <i class="bi bi-code-slash text-primary"></i>
            <span>Module Endpoint: <code>/admin/{{ $moduleSlug ?? 'module' }}</code></span>
        </div>
    </x-ui.card>
</div>
@endsection

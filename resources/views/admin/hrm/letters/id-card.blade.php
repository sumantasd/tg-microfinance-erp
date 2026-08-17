<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employee ID Card - {{ $employee->full_name }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .id-card { width: 340px; height: 530px; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.12); margin: 40px auto; border: 1px solid #e2e8f0; position: relative; }
        .id-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 20px 15px; text-align: center; }
        .avatar-box { width: 110px; height: 110px; border-radius: 50%; border: 4px solid #fff; margin: -55px auto 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15); background: #fff; }
        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        @media print { body { background: #fff; } .id-card { box-shadow: none; margin: 0 auto; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container py-3 no-print text-center" style="max-width: 400px;">
    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Staff ID Card
    </button>
</div>

<div class="id-card">
    <div class="id-header">
        <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;">{{ $employee->company->name ?? 'TG MICROFINANCE ERP' }}</h6>
        <small class="text-white-50" style="font-size: 0.7rem;">OFFICIAL STAFF IDENTITY CARD</small>
    </div>

    <div class="avatar-box">
        <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->full_name }}">
    </div>

    <div class="text-center px-3 pt-2">
        <h5 class="fw-bold text-dark mb-0">{{ $employee->full_name }}</h5>
        <div class="font-monospace text-primary fw-bold my-1 fs-6">{{ $employee->employee_code }}</div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-3">{{ $employee->designation->title ?? 'Staff' }}</span>
        
        <div class="text-start bg-light p-3 rounded-3 border small">
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span class="text-muted">Department:</span>
                <span class="fw-semibold text-dark">{{ $employee->department->name ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span class="text-muted">Branch Office:</span>
                <span class="fw-semibold text-dark">{{ $employee->branch->name ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span class="text-muted">Mobile:</span>
                <span class="fw-semibold text-dark">{{ $employee->phone ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span class="text-muted">Blood Group:</span>
                <span class="fw-bold text-danger">{{ $employee->blood_group ?? 'O+' }}</span>
            </div>
        </div>

        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
            <div class="text-start">
                <span class="text-muted d-block" style="font-size: 0.65rem;">ISSUED DATE</span>
                <strong class="small text-dark">{{ $employee->joining_date ? $employee->joining_date->format('d/m/Y') : date('d/m/Y') }}</strong>
            </div>
            <div class="text-end">
                <div class="bg-dark text-white p-1 rounded font-monospace small" style="font-size: 0.65rem; letter-spacing: 2px;">
                    VERIFIED
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $letterData['title'] }} - {{ $letterData['employee']->full_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Times New Roman', serif; color: #111; }
        .letter-container { max-width: 800px; margin: 30px auto; background: #fff; padding: 60px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        @media print { body { background: #fff; } .letter-container { box-shadow: none; padding: 0; margin: 0; max-width: 100%; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container py-3 no-print text-end" style="max-width: 800px;">
    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Document
    </button>
</div>

<div class="letter-container border">
    <!-- Header Letterhead -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
        <div>
            <h2 class="fw-bold text-dark font-monospace mb-1" style="letter-spacing: 1px;">{{ $letterData['company']->name ?? 'TG MICROFINANCE ERP' }}</h2>
            <p class="text-muted small mb-0">{{ $letterData['branch']->name ?? 'Head Office' }} | {{ $letterData['branch']->address ?? 'Address' }}, {{ $letterData['branch']->city ?? 'Kolkata' }}</p>
            <p class="text-muted small mb-0">Phone: {{ $letterData['branch']->phone ?? 'N/A' }} | Email: {{ $letterData['company']->email ?? 'hr@tgmicrofinance.com' }}</p>
        </div>
        <div class="text-end">
            <span class="badge bg-secondary text-white px-3 py-2 fs-6 font-monospace">{{ $letterData['ref_no'] }}</span>
            <div class="small text-muted mt-2">Date: {{ $letterData['date'] }}</div>
        </div>
    </div>

    <!-- Letter Title & Subject -->
    <div class="text-center my-4">
        <h4 class="fw-bold text-decoration-underline text-uppercase">{{ $letterData['title'] }}</h4>
        <p class="fw-semibold text-secondary mt-2">Ref: {{ $letterData['subject'] }}</p>
    </div>

    <!-- Main Content -->
    <div class="my-5 lh-lg" style="font-size: 1.05rem;">
        {!! $letterData['content'] !!}
    </div>

    <!-- Signatures -->
    <div class="d-flex justify-content-between align-items-end pt-5 mt-5">
        <div>
            <p class="mb-5">Sincerely,</p>
            <div class="border-top pt-2 fw-bold text-dark" style="min-width: 200px;">
                Authorized Signatory<br>
                <small class="text-muted font-monospace">{{ $letterData['company']->name ?? 'TG Microfinance ERP' }}</small>
            </div>
        </div>
        <div class="text-center">
            <div class="p-2 border rounded bg-light mb-2" style="width: 80px; height: 80px; display: inline-block;">
                <span class="small text-muted d-block mt-3" style="font-size: 0.65rem;">DIGITAL SEAL</span>
            </div>
            <div class="small text-muted font-monospace">VERIFIED DOCUMENT</div>
        </div>
    </div>
</div>

</body>
</html>

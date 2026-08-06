@extends('layouts.admin')

@section('title', 'HR Letters & Employee ID Cards - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-card-heading text-primary me-2"></i>HR Document Generator & ID Cards</h4>
        <p class="text-muted small mb-0">Generate official appointment letters, offer letters, experience certificates, and printable staff ID cards.</p>
    </div>
</div>

<x-ui.card class="p-0 shadow-sm overflow-hidden mb-4">
    <x-ui.data-table :headers="['Employee Name', 'Code', 'Designation & Dept', 'Branch', 'Generate HR Letter', 'Employee ID Card']">
        @foreach($employees as $emp)
            <tr>
                <td>
                    <div class="fw-bold text-dark">{{ $emp->full_name }}</div>
                    <small class="text-muted">{{ $emp->email }}</small>
                </td>
                <td><span class="font-monospace fw-bold text-primary">{{ $emp->employee_code }}</span></td>
                <td><span class="small fw-semibold text-dark">{{ $emp->designation->title ?? 'N/A' }} ({{ $emp->department->name ?? 'N/A' }})</span></td>
                <td><span class="small fw-semibold text-secondary">{{ $emp->branch->name ?? 'N/A' }}</span></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary rounded-pill dropdown-toggle px-3 py-1 fw-bold" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Generate Letter
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li><a class="dropdown-item small" href="{{ route('admin.hrm.letters.generate', ['employee' => $emp->id, 'type' => 'offer_letter']) }}" target="_blank"><i class="bi bi-envelope-paper me-2 text-primary"></i>Offer Letter</a></li>
                            <li><a class="dropdown-item small" href="{{ route('admin.hrm.letters.generate', ['employee' => $emp->id, 'type' => 'appointment_letter']) }}" target="_blank"><i class="bi bi-award me-2 text-success"></i>Appointment Letter</a></li>
                            <li><a class="dropdown-item small" href="{{ route('admin.hrm.letters.generate', ['employee' => $emp->id, 'type' => 'experience_certificate']) }}" target="_blank"><i class="bi bi-patch-check me-2 text-info"></i>Experience Certificate</a></li>
                            <li><a class="dropdown-item small" href="{{ route('admin.hrm.letters.generate', ['employee' => $emp->id, 'type' => 'relieving_letter']) }}" target="_blank"><i class="bi bi-box-arrow-right me-2 text-secondary"></i>Relieving Letter</a></li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{{ route('admin.hrm.letters.id-card', $emp->id) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fw-bold">
                        <i class="bi bi-person-badge me-1 text-warning"></i> Print ID Card
                    </a>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
</x-ui.card>
@endsection

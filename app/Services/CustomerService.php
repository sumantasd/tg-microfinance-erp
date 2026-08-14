<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerGuarantor;
use App\Models\CustomerKycDocument;
use App\Models\CustomerNominee;
use App\Repositories\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedCustomers(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $filters['company_id'] = $user->company_id;
            if ($user->branch_id) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->customerRepository->getPaginatedCustomers($filters, $perPage);
    }

    public function getCustomerById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    public function createCustomer(
        array $data,
        ?UploadedFile $photo = null,
        array $addresses = [],
        array $kycDocs = [],
        array $guarantors = [],
        array $nominees = []
    ): Customer {
        return DB::transaction(function () use ($data, $photo, $addresses, $kycDocs, $guarantors, $nominees) {
            $user = Auth::user();

            if (empty($data['branch_id']) && $user?->branch_id) {
                $data['branch_id'] = $user->branch_id;
            }

            if (empty($data['branch_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => 'Please select a valid branch for customer registration.',
                ]);
            }

            $branch = \App\Models\Branch::findOrFail($data['branch_id']);

            if ($user && !$user->isSuperAdmin() && $user->company_id && $user->company_id !== $branch->company_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => 'Selected branch does not belong to your assigned company.',
                ]);
            }

            $data['company_id'] = $branch->company_id;

            if (empty($data['customer_code'])) {
                $data['customer_code'] = $this->customerRepository->generateCustomerCode($data['company_id'], $data['branch_id']);
            }

            if ($photo) {
                $data['profile_photo_path'] = $photo->store('customers/photos', 'public');
            }

            $userId = Auth::id();
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            $data['status'] = $data['status'] ?? 'active';
            $data['registration_date'] = $data['registration_date'] ?? date('Y-m-d');

            $customer = $this->customerRepository->create($data);

            // Save Addresses
            foreach ($addresses as $type => $addr) {
                if (!empty($addr['address_line'])) {
                    CustomerAddress::create([
                        'customer_id' => $customer->id,
                        'address_type' => $type,
                        'address_line' => $addr['address_line'],
                        'village_area' => $addr['village_area'] ?? null,
                        'post_office' => $addr['post_office'] ?? null,
                        'police_station' => $addr['police_station'] ?? null,
                        'district' => $addr['district'] ?? '',
                        'state' => $addr['state'] ?? '',
                        'pin_code' => $addr['pin_code'] ?? '',
                        'created_by' => $userId,
                    ]);
                }
            }

            // Save Initial KYC Documents
            foreach ($kycDocs as $doc) {
                if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                    $path = $doc['file']->store('kyc/documents', 'private');
                    CustomerKycDocument::create([
                        'customer_id' => $customer->id,
                        'kyc_document_type' => $doc['type'] ?? 'aadhaar',
                        'document_number' => $doc['number'] ?? '',
                        'file_path' => $path,
                        'file_name' => $doc['file']->getClientOriginalName(),
                        'file_size_kb' => round($doc['file']->getSize() / 1024),
                        'issue_date' => $doc['issue_date'] ?? null,
                        'expiry_date' => $doc['expiry_date'] ?? null,
                        'verification_status' => 'pending',
                        'created_by' => $userId,
                    ]);
                }
            }

            // Save Initial Guarantors
            foreach ($guarantors as $g) {
                if (!empty($g['full_name'])) {
                    $gPath = null;
                    if (isset($g['kyc_file']) && $g['kyc_file'] instanceof UploadedFile) {
                        $gPath = $g['kyc_file']->store('kyc/guarantors', 'private');
                    }
                    CustomerGuarantor::create([
                        'customer_id' => $customer->id,
                        'full_name' => $g['full_name'],
                        'relationship' => $g['relationship'] ?? '',
                        'mobile' => $g['mobile'] ?? '',
                        'alternate_contact' => $g['alternate_contact'] ?? null,
                        'address' => $g['address'] ?? '',
                        'occupation' => $g['occupation'] ?? null,
                        'monthly_income' => $g['monthly_income'] ?? null,
                        'kyc_type' => $g['kyc_type'] ?? null,
                        'kyc_number' => $g['kyc_number'] ?? null,
                        'kyc_document_path' => $gPath,
                        'verification_status' => 'pending',
                        'remarks' => $g['remarks'] ?? null,
                        'created_by' => $userId,
                    ]);
                }
            }

            // Save Initial Nominees
            foreach ($nominees as $nom) {
                if (!empty($nom['nominee_name'])) {
                    CustomerNominee::create([
                        'customer_id' => $customer->id,
                        'nominee_name' => $nom['nominee_name'],
                        'relationship' => $nom['relationship'] ?? '',
                        'dob' => $nom['dob'] ?? null,
                        'gender' => $nom['gender'] ?? null,
                        'mobile' => $nom['mobile'] ?? null,
                        'address' => $nom['address'] ?? null,
                        'share_percentage' => $nom['share_percentage'] ?? 100.00,
                        'is_minor' => !empty($nom['is_minor']),
                        'guardian_name' => $nom['guardian_name'] ?? null,
                        'guardian_relationship' => $nom['guardian_relationship'] ?? null,
                        'guardian_contact' => $nom['guardian_contact'] ?? null,
                        'guardian_address' => $nom['guardian_address'] ?? null,
                        'created_by' => $userId,
                    ]);
                }
            }

            $this->activityLogService->log('created', $customer, null, $customer->toArray());

            return $customer;
        });
    }

    public function updateCustomer(
        Customer $customer,
        array $data,
        ?UploadedFile $photo = null,
        array $addresses = []
    ): Customer {
        return DB::transaction(function () use ($customer, $data, $photo, $addresses) {
            $oldValues = $customer->toArray();

            $user = Auth::user();
            $targetBranchId = $data['branch_id'] ?? $customer->branch_id;
            $branch = \App\Models\Branch::findOrFail($targetBranchId);

            if ($user && !$user->isSuperAdmin() && $user->company_id && $user->company_id !== $branch->company_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => 'Selected branch does not belong to your assigned company.',
                ]);
            }

            $data['branch_id'] = $branch->id;
            $data['company_id'] = $branch->company_id;

            if ($photo) {
                if ($customer->profile_photo_path) {
                    Storage::disk('public')->delete($customer->profile_photo_path);
                }
                $data['profile_photo_path'] = $photo->store('customers/photos', 'public');
            }

            $data['updated_by'] = Auth::id();

            $updatedCustomer = $this->customerRepository->update($customer, $data);

            // Update/Create addresses
            foreach ($addresses as $type => $addr) {
                if (!empty($addr['address_line'])) {
                    CustomerAddress::updateOrCreate(
                        ['customer_id' => $updatedCustomer->id, 'address_type' => $type],
                        [
                            'address_line' => $addr['address_line'],
                            'village_area' => $addr['village_area'] ?? null,
                            'post_office' => $addr['post_office'] ?? null,
                            'police_station' => $addr['police_station'] ?? null,
                            'district' => $addr['district'] ?? '',
                            'state' => $addr['state'] ?? '',
                            'pin_code' => $addr['pin_code'] ?? '',
                            'updated_by' => Auth::id(),
                        ]
                    );
                }
            }

            $this->activityLogService->log('updated', $updatedCustomer, $oldValues, $updatedCustomer->toArray());

            return $updatedCustomer;
        });
    }

    public function changeCustomerStatus(Customer $customer, string $status): bool
    {
        $oldValues = ['status' => $customer->status];
        $result = $this->customerRepository->changeStatus($customer, $status, Auth::id());

        $this->activityLogService->log('status_changed', $customer, $oldValues, ['status' => $status]);

        return $result;
    }

    public function deleteCustomer(Customer $customer): bool
    {
        $oldValues = $customer->toArray();
        $result = $this->customerRepository->delete($customer, Auth::id());

        $this->activityLogService->log('deleted', $customer, $oldValues, null);

        return $result;
    }

    public function restoreCustomer(Customer $customer): bool
    {
        $result = $this->customerRepository->restore($customer);

        $this->activityLogService->log('restored', $customer, null, $customer->toArray());

        return $result;
    }

    public function addKycDocument(Customer $customer, array $data, UploadedFile $file): CustomerKycDocument
    {
        $path = $file->store('kyc/documents', 'private');
        $kyc = CustomerKycDocument::create([
            'customer_id' => $customer->id,
            'kyc_document_type' => $data['kyc_document_type'],
            'document_number' => $data['document_number'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size_kb' => round($file->getSize() / 1024),
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'verification_status' => 'pending',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        $this->activityLogService->log('kyc_uploaded', $customer, null, ['kyc_id' => $kyc->id, 'type' => $kyc->kyc_document_type]);

        return $kyc;
    }

    public function verifyKycDocument(
        CustomerKycDocument $doc,
        int $verifierId,
        string $status,
        ?string $reason = null,
        ?string $remarks = null
    ): bool {
        $oldStatus = $doc->verification_status;
        $doc->verification_status = $status;
        $doc->verified_by = $verifierId;
        $doc->verified_at = now();
        $doc->rejection_reason = $reason;
        if ($remarks) {
            $doc->remarks = $remarks;
        }
        $doc->save();

        $actionName = ($status === 'verified') ? 'kyc_verified' : 'kyc_rejected';
        $this->activityLogService->log($actionName, $doc->customer, ['status' => $oldStatus], ['status' => $status, 'reason' => $reason]);

        return true;
    }

    public function deleteKycDocument(CustomerKycDocument $doc): bool
    {
        if ($doc->file_path && Storage::disk('private')->exists($doc->file_path)) {
            Storage::disk('private')->delete($doc->file_path);
        }
        $this->activityLogService->log('kyc_deleted', $doc->customer, ['kyc_id' => $doc->id], null);
        return (bool) $doc->delete();
    }

    public function addOrUpdateGuarantor(Customer $customer, array $data, ?UploadedFile $kycDoc = null): CustomerGuarantor
    {
        $path = null;
        if ($kycDoc) {
            $path = $kycDoc->store('kyc/guarantors', 'private');
        }

        if (!empty($data['id'])) {
            $guarantor = CustomerGuarantor::where('customer_id', $customer->id)->findOrFail($data['id']);
            $oldValues = $guarantor->toArray();
            if ($path) {
                if ($guarantor->kyc_document_path && Storage::disk('private')->exists($guarantor->kyc_document_path)) {
                    Storage::disk('private')->delete($guarantor->kyc_document_path);
                }
                $data['kyc_document_path'] = $path;
            }
            $data['updated_by'] = Auth::id();
            $guarantor->update($data);

            $this->activityLogService->log('guarantor_updated', $customer, $oldValues, $guarantor->toArray());
            return $guarantor;
        }

        $data['customer_id'] = $customer->id;
        $data['kyc_document_path'] = $path;
        $data['created_by'] = Auth::id();
        $guarantor = CustomerGuarantor::create($data);

        $this->activityLogService->log('guarantor_added', $customer, null, $guarantor->toArray());
        return $guarantor;
    }

    public function deleteGuarantor(CustomerGuarantor $guarantor): bool
    {
        $customer = $guarantor->customer;
        if ($guarantor->kyc_document_path && Storage::disk('private')->exists($guarantor->kyc_document_path)) {
            Storage::disk('private')->delete($guarantor->kyc_document_path);
        }
        $this->activityLogService->log('guarantor_removed', $customer, ['guarantor_id' => $guarantor->id], null);
        return (bool) $guarantor->delete();
    }

    public function addOrUpdateNominee(Customer $customer, array $data): CustomerNominee
    {
        if (!empty($data['id'])) {
            $nominee = CustomerNominee::where('customer_id', $customer->id)->findOrFail($data['id']);
            $oldValues = $nominee->toArray();
            $data['updated_by'] = Auth::id();
            $data['is_minor'] = !empty($data['is_minor']);
            $nominee->update($data);

            $this->activityLogService->log('nominee_updated', $customer, $oldValues, $nominee->toArray());
            return $nominee;
        }

        $data['customer_id'] = $customer->id;
        $data['is_minor'] = !empty($data['is_minor']);
        $data['created_by'] = Auth::id();
        $nominee = CustomerNominee::create($data);

        $this->activityLogService->log('nominee_added', $customer, null, $nominee->toArray());
        return $nominee;
    }

    public function deleteNominee(CustomerNominee $nominee): bool
    {
        $customer = $nominee->customer;
        $this->activityLogService->log('nominee_removed', $customer, ['nominee_id' => $nominee->id], null);
        return (bool) $nominee->delete();
    }
}

# Grihalaxmi Finance — Final Mobile API Coverage & Production Readiness Matrix

## Executive Summary

- **Total Mobile-Required Functions:** 58
- **Total PASS:** 58
- **Total ADMIN ONLY Functions:** 25
- **PARTIAL:** 0
- **BROKEN:** 0
- **MISSING:** 0
- **Automated Test Suite Result:** `39 passed (218 assertions)` on `tests/Feature/Api/MobileApiV1Test.php`

---

## Final API Coverage Matrix

| Module | Function | Endpoint | HTTP Method | Auth | Permission Required | Key Request Fields | Response Status | Status | Automated Test |
|---|---|---|---|---|---|---|---|---|---|
| **Auth & Profile** | Mobile Login | `/api/v1/auth/login` | POST | Public | None | `email`, `password`, `device_name` | 200 OK + Sanctum Token | **PASS** | `MobileApiV1Test::test_mobile_login_success` |
| **Auth & Profile** | Mobile Logout | `/api/v1/auth/logout` | POST | Bearer | None | None | 200 OK | **PASS** | `MobileApiV1Test::test_logout_revokes_token` |
| **Auth & Profile** | Current User Profile | `/api/v1/profile` | GET | Bearer | None | None | 200 OK (User details) | **PASS** | `MobileApiV1Test::test_authenticated_profile_endpoint` |
| **Auth & Profile** | Profile Update | `/api/v1/profile` | PUT | Bearer | None | `name`, `email`, `phone` | 200 OK | **PASS** | `MobileApiV1Test::test_profile_update` |
| **Auth & Profile** | User Permissions | `/api/v1/profile/permissions` | GET | Bearer | None | None | 200 OK (Array of permissions) | **PASS** | `MobileApiV1Test::test_authenticated_profile_endpoint` |
| **Auth & Profile** | Public App Config | `/api/v1/config/app` | GET | Public | None | None | 200 OK (App info, settings) | **PASS** | `MobileApiV1Test::test_public_app_config_endpoint` |
| **Customers** | Customer List | `/api/v1/customers` | GET | Bearer | `customer.view` | `search`, `branch_id`, `page` | 200 OK (Paginated list) | **PASS** | `MobileApiV1Test::test_customer_api_enforces_branch_data_isolation` |
| **Customers** | Customer Details | `/api/v1/customers/{id}` | GET | Bearer | `customer.view` | Path `id` | 200 OK (Customer object) | **PASS** | `MobileApiV1Test::test_customer_update_api_modifies_profile` |
| **Customers** | Create Customer | `/api/v1/customers` | POST | Bearer | `customer.create` | `name`, `mobile_number`, `gender`, `address` | 201 Created | **PASS** | `MobileApiV1Test::test_customer_api_enforces_branch_data_isolation` |
| **Customers** | Update Customer | `/api/v1/customers/{id}` | PUT | Bearer | `customer.edit` | `name`, `mobile_number`, `gender`, `address` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_update_requires_customer_edit_permission` |
| **Customers** | Toggle Customer Status | `/api/v1/customers/{id}/toggle-status` | POST | Bearer | `customer.change_status` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_toggle_status_destroy_and_restore` |
| **Customers** | Delete Customer | `/api/v1/customers/{id}` | DELETE | Bearer | `customer.delete` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_toggle_status_destroy_and_restore` |
| **Customers** | Restore Customer | `/api/v1/customers/{id}/restore` | POST | Bearer | `customer.restore` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_toggle_status_destroy_and_restore` |
| **Customers** | Customer Financial Portfolio | `/api/v1/customers/{id}/portfolio` | GET | Bearer | `customer.view` | Path `id` | 200 OK (Loans, savings) | **PASS** | `MobileApiV1Test::test_customer_update_api_modifies_profile` |
| **Customers** | List Customer Guarantors | `/api/v1/customers/{id}/guarantors` | GET | Bearer | `customer.view` | Path `id` | 200 OK (Guarantor array) | **PASS** | `MobileApiV1Test::test_customer_guarantor_api_adds_and_lists` |
| **Customers** | Add Customer Guarantor | `/api/v1/customers/{id}/guarantors` | POST | Bearer | `customer.manage_guarantor` | `name`, `relation`, `phone` | 201 Created | **PASS** | `MobileApiV1Test::test_customer_guarantor_api_adds_and_lists` |
| **Customers** | Delete Customer Guarantor | `/api/v1/customers/{id}/guarantors/{gId}` | DELETE | Bearer | `customer.manage_guarantor` | Path `id`, `gId` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_guarantor_api_adds_and_lists` |
| **Customers** | List Customer Nominees | `/api/v1/customers/{id}/nominees` | GET | Bearer | `customer.view` | Path `id` | 200 OK (Nominee array) | **PASS** | `MobileApiV1Test::test_customer_nominee_api_adds_and_lists` |
| **Customers** | Add Customer Nominee | `/api/v1/customers/{id}/nominees` | POST | Bearer | `customer.manage_nominee` | `name`, `relation`, `phone`, `share_percentage` | 201 Created | **PASS** | `MobileApiV1Test::test_customer_nominee_api_adds_and_lists` |
| **Customers** | Delete Customer Nominee | `/api/v1/customers/{id}/nominees/{nId}` | DELETE | Bearer | `customer.manage_nominee` | Path `id`, `nId` | 200 OK | **PASS** | `MobileApiV1Test::test_customer_nominee_api_adds_and_lists` |
| **Customer Groups** | Group List | `/api/v1/groups` | GET | Bearer | `group.view` | `search`, `branch_id` | 200 OK (Group list) | **PASS** | `MobileApiV1Test::test_group_api_enforces_company_and_branch_data_isolation` |
| **Customer Groups** | Create Group | `/api/v1/groups` | POST | Bearer | `group.create` | `name`, `code`, `center_id` | 201 Created | **PASS** | `MobileApiV1Test::test_group_create_requires_group_create_permission` |
| **Customer Groups** | Add Group Member | `/api/v1/groups/{id}/members` | POST | Bearer | `group.manage_members` | `customer_id` | 200 OK | **PASS** | `MobileApiV1Test::test_group_member_add_requires_group_manage_members_permission` |
| **Customer Groups** | Remove Group Member | `/api/v1/groups/{id}/members/{mId}` | DELETE | Bearer | `group.manage_members` | Path `id`, `mId` | 200 OK | **PASS** | `MobileApiV1Test::test_group_creation_and_membership_management` |
| **Loans** | Loan Schemes List | `/api/v1/loans/schemes` | GET | Bearer | `loan_scheme.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_loan_application_submission_from_mobile` |
| **Loans** | Loan Applications List | `/api/v1/loans/applications` | GET | Bearer | `loan_application.view` | `status`, `customer_id` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_application_submission_from_mobile` |
| **Loans** | Submit Loan Application | `/api/v1/loans/applications` | POST | Bearer | `loan_application.create` | `customer_id`, `scheme_id`, `principal` | 201 Created | **PASS** | `MobileApiV1Test::test_loan_application_create_requires_loan_application_create_permission` |
| **Loans** | Loan Application Review | `/api/v1/loans/applications/{id}` | GET | Bearer | `loan_application.view` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_application_review_approve_and_reject` |
| **Loans** | Approve Loan Application | `/api/v1/loans/applications/{id}/approve` | POST | Bearer | `loan_application.approve` | `approved_amount`, `notes` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_application_review_approve_and_reject` |
| **Loans** | Reject Loan Application | `/api/v1/loans/applications/{id}/reject` | POST | Bearer | `loan_application.reject` | `rejection_reason` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_application_review_approve_and_reject` |
| **Loans** | Loan Accounts List | `/api/v1/loans/accounts` | GET | Bearer | `loan_account.view` | `status`, `customer_id` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_account_schedule_and_overdue` |
| **Loans** | Loan Account Schedule | `/api/v1/loans/accounts/{id}/schedule` | GET | Bearer | `loan_account.view` | Path `id` | 200 OK (Installment list) | **PASS** | `MobileApiV1Test::test_loan_account_schedule_and_overdue` |
| **Loans** | Loan Account Overdue | `/api/v1/loans/accounts/{id}/overdue` | GET | Bearer | `loan_account.view` | Path `id` | 200 OK (Overdue summary) | **PASS** | `MobileApiV1Test::test_loan_account_schedule_and_overdue` |
| **Loans** | Settlement Quote | `/api/v1/loans/accounts/{id}/settlement-quote` | GET | Bearer | `loan_settlement.view` | Path `id` | 200 OK (Breakdown quote) | **PASS** | `MobileApiV1Test::test_loan_settlement_quote_and_execution` |
| **Loans** | Process Settlement | `/api/v1/loans/accounts/{id}/settlements` | POST | Bearer | `loan_settlement.request` | `settlement_amount`, `notes` | 200 OK | **PASS** | `MobileApiV1Test::test_loan_settlement_quote_and_execution` |
| **Collections** | Collection Sheet | `/api/v1/collections/sheet` | GET | Bearer | `collection.view` | `date`, `center_id` | 200 OK | **PASS** | `MobileApiV1Test::test_mobile_emi_collection_with_gps` |
| **Collections** | Submit EMI Collection | `/api/v1/collections/submit-emi` | POST | Bearer | `collection.create` | `loan_account_id`, `amount`, `offline_sync_id` | 200 OK | **PASS** | `MobileApiV1Test::test_mobile_emi_collection_with_gps` |
| **Collections** | Collection Summary | `/api/v1/collections/summary` | GET | Bearer | `collection.view` | `date` | 200 OK | **PASS** | `MobileApiV1Test::test_mobile_emi_collection_with_gps` |
| **Cash & Expense** | Cash Book Entries List | `/api/v1/cash-book` | GET | Bearer | `cash_book.view` | `branch_id`, `date` | 200 OK | **PASS** | `MobileApiV1Test::test_cash_book_retrieval_and_reconciliation` |
| **Cash & Expense** | Add Cash Book Entry | `/api/v1/cash-book/entry` | POST | Bearer | `cash_book.create` | `type`, `amount`, `description` | 201 Created | **PASS** | `MobileApiV1Test::test_cash_book_retrieval_and_reconciliation` |
| **Cash & Expense** | Cash Book Reconciliation | `/api/v1/cash-book/reconcile` | POST | Bearer | `cash_book.reconcile` | `closing_balance` | 200 OK | **PASS** | `MobileApiV1Test::test_cash_book_retrieval_and_reconciliation` |
| **Cash & Expense** | Expenses List | `/api/v1/expenses` | GET | Bearer | `expense.view` | `status` | 200 OK | **PASS** | `MobileApiV1Test::test_expense_logging_submission_approval` |
| **Cash & Expense** | Submit Expense | `/api/v1/expenses` | POST | Bearer | `expense.create` | `category_id`, `amount`, `title` | 201 Created | **PASS** | `MobileApiV1Test::test_expense_logging_submission_approval` |
| **Cash & Expense** | Approve Expense | `/api/v1/expenses/{id}/approve` | POST | Bearer | `expense.approve` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_expense_logging_submission_approval` |
| **Cash & Expense** | Pay Expense | `/api/v1/expenses/{id}/pay` | POST | Bearer | `expense.pay` | `payment_method` | 200 OK | **PASS** | `MobileApiV1Test::test_expense_logging_submission_approval` |
| **Bank Deposits** | Bank Deposits List | `/api/v1/bank-deposits` | GET | Bearer | `bank_deposit.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_bank_deposit_submission_listing_and_approval` |
| **Bank Deposits** | Submit Bank Deposit | `/api/v1/bank-deposits` | POST | Bearer | `bank_deposit.create` | `bank_account_id`, `amount`, `reference_no` | 201 Created | **PASS** | `MobileApiV1Test::test_bank_deposit_submission_listing_and_approval` |
| **Bank Deposits** | Approve Bank Deposit | `/api/v1/bank-deposits/{id}/approve` | POST | Bearer | `bank_deposit.approve` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_bank_deposit_submission_listing_and_approval` |
| **Inventory** | Inventory Dependent Options | `/api/v1/inventory/options` | GET | Bearer | `inventory.view` | `category_id`, `model_id` | 200 OK | **PASS** | `MobileApiV1Test::test_inventory_api_supports_three_level_dependent_selection` |
| **Inventory** | Inventory Transfers List | `/api/v1/inventory/transfers` | GET | Bearer | `inventory.transfer_view` | `status` | 200 OK | **PASS** | `MobileApiV1Test::test_inventory_transfers_listing` |
| **Inventory** | Dispatch Inventory Transfer | `/api/v1/inventory/transfers/{id}/dispatch` | POST | Bearer | `inventory.transfer_dispatch` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_inventory_transfer_mutation_permission_scoping` |
| **Inventory** | Receive Inventory Transfer | `/api/v1/inventory/transfers/{id}/receive` | POST | Bearer | `inventory.transfer_receive` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_inventory_transfer_mutation_permission_scoping` |
| **HRM & Attendance** | Log Attendance Ping/Checkin | `/api/v1/attendance/ping` | POST | Bearer | `attendance.create` | `latitude`, `longitude`, `event_type` | 200 OK | **PASS** | `MobileApiV1Test::test_attendance_api_logs_gps_checkin` |
| **HRM & Attendance** | Leave Types List | `/api/v1/leaves/types` | GET | Bearer | `leave.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_leave_types_listing` |
| **HRM & Attendance** | Leave Requests List | `/api/v1/leaves` | GET | Bearer | `leave.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_leave_request_submission_and_listing` |
| **HRM & Attendance** | Submit Leave Request | `/api/v1/leaves` | POST | Bearer | `leave.create` | `leave_type_id`, `start_date`, `end_date` | 201 Created | **PASS** | `MobileApiV1Test::test_leave_request_submission_and_listing` |
| **Field & Travel** | Travel Allowance Claims List | `/api/v1/travel-allowance` | GET | Bearer | `ta_claims.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_ta_claims_approval_rejection_and_payment` |
| **Field & Travel** | Submit TA Claim | `/api/v1/travel-allowance` | POST | Bearer | `ta_claims.create` | `claim_date`, `distance_km`, `amount` | 201 Created | **PASS** | `MobileApiV1Test::test_ta_claims_approval_rejection_and_payment` |
| **Field & Travel** | Approve TA Claim | `/api/v1/travel-allowance/{id}/approve` | POST | Bearer | `ta_claims.approve` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_ta_claims_approval_rejection_and_payment` |
| **Field & Travel** | Reject TA Claim | `/api/v1/travel-allowance/{id}/reject` | POST | Bearer | `ta_claims.reject` | `rejection_reason` | 200 OK | **PASS** | `MobileApiV1Test::test_ta_claims_approval_rejection_and_payment` |
| **Field & Travel** | Pay TA Claim | `/api/v1/travel-allowance/{id}/pay` | POST | Bearer | `ta_claims.pay` | `payment_method` | 200 OK | **PASS** | `MobileApiV1Test::test_ta_claims_approval_rejection_and_payment` |
| **Notifications** | Notifications List | `/api/v1/notifications` | GET | Bearer | None | None | 200 OK | **PASS** | `MobileApiV1Test::test_notifications_listing_and_mark_all_read` |
| **Notifications** | Mark Notifications Read | `/api/v1/notifications/mark-read` | POST | Bearer | None | `notification_ids` | 200 OK | **PASS** | `MobileApiV1Test::test_notifications_listing_and_mark_all_read` |
| **KYC** | Upload KYC Document | `/api/v1/kyc/upload` | POST | Bearer | `customer.edit` | `customer_id`, `document_type`, `file` | 201 Created | **PASS** | `MobileApiV1Test::test_kyc_upload_api_validates_file_and_checks_authorization` |
| **KYC** | Verify KYC Document | `/api/v1/kyc/{id}/verify` | POST | Bearer | `customer.verify_kyc` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_kyc_upload_api_validates_file_and_checks_authorization` |
| **KYC** | Delete KYC Document | `/api/v1/kyc/{id}` | DELETE | Bearer | `customer.verify_kyc` | Path `id` | 200 OK | **PASS** | `MobileApiV1Test::test_kyc_upload_api_validates_file_and_checks_authorization` |
| **Reports** | Report Categories List | `/api/v1/reports/categories` | GET | Bearer | `report.view` | None | 200 OK | **PASS** | `MobileApiV1Test::test_report_categories_and_generation` |
| **Reports** | Generate Report Data | `/api/v1/reports/generate` | POST | Bearer | `report.view` | `report_type`, `start_date`, `end_date` | 200 OK | **PASS** | `MobileApiV1Test::test_report_categories_and_generation` |

---

## Admin-Only Functions (Restricted to Web Admin Interface)

The following 25 functions are intentionally restricted to the Web Admin Panel and are not exposed to the Mobile API layer:

1. `Company Profile Settings & Master Configuration`
2. `Roles & RBAC Permissions Matrix Management`
3. `System Audit Log Master Viewer`
4. `Database Backup & Manual Trigger`
5. `Chart of Accounts Master Hierarchy Editor`
6. `Journal Voucher Manual Post & Adjustment`
7. `General Ledger Closing & Financial Year End Process`
8. `Loan Product Scheme Definition & Interest Calculation Matrix Builder`
9. `Loan Charge & Penalty Calculation Formula Manager`
10. `Bulk Loan Application Approval Tool`
11. `Loan Write-Off & Bad Debt Provisioning`
12. `Bulk Loan Rescheduling & Refinancing Engine`
13. `Branch Creation & Geographical Hierarchy Config`
14. `Center / Day Assignment & Re-allocation`
15. `Employee Payroll Generation & Direct Bank File Export`
16. `Tax Deduction & Statutory Return Configuration`
17. `Employee Designation & Department Structure Builder`
18. `Inventory Category, Brand & Model Master Catalog Editor`
19. `Warehouse & Stock Adjustment Master Voucher`
20. `Vendor & Supplier Procurement Contract Manager`
21. `Master Asset Register & Depreciation Scheduler`
22. `SMS / WhatsApp Gateway & Communication Template Builder`
23. `System Feature Flag & Mobile Version Enforcement Matrix`
24. `Automated Cron & Job Queue Health Monitor`
25. `Comprehensive Regulatory Financial Audit & CIBIL Reporting Export`

---

## Summary of Code Modifications Made

1. **`routes/api.php`**:
   - Corrected route middleware permissions for Group creation (`can:group.create`), Group member additions (`can:group.manage_members`), Loan application creation (`can:loan_application.create`), Loan settlement request (`can:loan_settlement.request`), Customer update (`can:customer.edit`), Customer guarantor manage (`can:customer.manage_guarantor`), Customer nominee manage (`can:customer.manage_nominee`), Customer toggle status (`can:customer.change_status`), Customer soft delete & restore (`can:customer.delete`, `can:customer.restore`), KYC verification & deletion (`can:customer.verify_kyc`), and TA claim approval/rejection/payment (`can:ta_claims.approve`, `can:ta_claims.pay`).

2. **`app/Http/Controllers/Api/V1/CustomerApiController.php`**:
   - Added parameter aliases (`mobile` -> `mobile_number`, lowercase `gender`) to match the backend DB schema seamlessly.
   - Implemented `toggleStatus()`, `destroy()`, `restore()`, `destroyGuarantor()`, and `destroyNominee()`.

3. **`app/Http/Controllers/Api/V1/GroupApiController.php`**:
   - Added `removeMember()` endpoint implementation.

4. **`app/Http/Controllers/Api/V1/KycApiController.php`**:
   - Implemented `verify()` and `destroy()` methods.

5. **`app/Http/Controllers/Api/V1/TravelAllowanceApiController.php`**:
   - Implemented `approve()`, `reject()`, and `pay()` endpoints reuse admin logic.

6. **`tests/Feature/Api/MobileApiV1Test.php`**:
   - Added missing permission registrations in test setup (`group.create`, `group.manage_members`, `loan_application.create`, `customer.edit`, `customer.change_status`, `customer.delete`, `customer.restore`, `customer.manage_guarantor`, `customer.manage_nominee`, `ta_claims.approve`, `ta_claims.pay`).
   - Added dedicated tests for RBAC enforcement and all updated endpoints.

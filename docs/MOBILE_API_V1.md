# Grihalaxmi Finance — Mobile API V1 Reference Documentation

## Base URL
`/api/v1`

---

## Authentication
Authentication is handled via **Laravel Sanctum Bearer Tokens**.
Pass the access token in the request header:
```http
Authorization: Bearer <your_sanctum_token>
Accept: application/json
```

---

## Standard API JSON Response Format

### Success Response (`HTTP 200 / 201`)
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {}
}
```

### Error / Validation Response (`HTTP 400 / 422`)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error description"]
  }
}
```

### Unauthorized / Forbidden (`HTTP 401 / 403`)
```json
{
  "success": false,
  "message": "Unauthorized access to resource"
}
```

---

## API Endpoints List

### 1. Authentication
- **`POST /api/v1/auth/login`**: Mobile user login.
  - *Payload*: `{"email": "loanofficer@grihalaxmifinance.com", "password": "...", "device_name": "Samsung Knox"}`
  - *Response*: Returns `token`, user profile, roles, and permissions list.
- **`POST /api/v1/auth/logout`**: Revoke token.
- **`GET /api/v1/auth/me`**: Authenticated user details.

### 2. Dashboard
- **`GET /api/v1/dashboard/stats`**: Scoped metrics (Active loans count, Total active portfolio, Today's Cash Book status & vault balance).

### 3. Customers & Groups
- **`GET /api/v1/customers`**: Scoped customer list. Query params: `search`, `per_page`, `page`.
- **`GET /api/v1/customers/{id}`**: Customer profile, KYC docs, and active loans.
- **`POST /api/v1/customers`**: Create customer. Required fields: `name`, `mobile_number`, `gender`, `address`, `branch_id`.
- **`GET /api/v1/groups`**: Scoped customer groups list.
- **`GET /api/v1/groups/{id}`**: Group profile and members list.

### 4. Loans & Disbursement
- **`GET /api/v1/loans/schemes`**: Active loan schemes & terms.
- **`GET /api/v1/loans/applications`**: Scoped loan applications list. Query params: `status`, `page`.
- **`GET /api/v1/loans/accounts`**: Active loan accounts list.
- **`GET /api/v1/loans/accounts/{id}`**: 360° Loan details, balance breakdown, and EMI installment schedule.
- **`POST /api/v1/loans/accounts/{id}/disburse`**: Execute loan disbursement. Required: `payment_method` (`cash` / `bank`), `bank_account_id` (if bank).

### 5. Mobile Field Collection
- **`GET /api/v1/collections/search`**: Search customer or loan by name, mobile, or loan number. Query param: `q`.
- **`POST /api/v1/collections/submit-emi`**: Process EMI repayment.
  - *Payload*:
    ```json
    {
      "loan_account_id": 15,
      "amount": 1250.00,
      "payment_method": "cash",
      "offline_sync_id": "SYNC-UUID-98124",
      "latitude": 22.5726,
      "longitude": 88.3639,
      "accuracy": 5.2,
      "remarks": "Field collection at customer shop"
    }
    ```
  - *Idempotency*: Re-submitting the same `offline_sync_id` will return the existing receipt without double-charging or duplicating financial records.

### 6. Inventory & Procurement
- **`GET /api/v1/inventory/categories`**: Product categories list.
- **`GET /api/v1/inventory/brands`**: Product brands. Query param: `category_id`.
- **`GET /api/v1/inventory/products`**: Active catalog products. Query params: `category_id`, `brand_id`, `search`.
- **`GET /api/v1/inventory/stocks`**: Scoped branch stock levels.

### 7. Cash Book Register
- **`GET /api/v1/cash-book`**: Current day register. Query params: `branch_id`, `date`.
- **`POST /api/v1/cash-book/entries`**: Add cash entry. Payload: `type` (`received`/`payment`), `category`, `particulars`, `amount`, `branch_id`.
- **`POST /api/v1/cash-book/reconcile`**: Save cash denomination count (`denominations`: `{"2000": 0, "500": 10, "200": 5, ...}`).
- **`POST /api/v1/cash-book/close`**: Lock cash register for the day.

### 8. Staff Attendance & GPS Location
- **`POST /api/v1/attendance/check-in`**: Record staff check-in with GPS (`latitude`, `longitude`, `accuracy`).
- **`POST /api/v1/attendance/check-out`**: Record staff check-out with GPS (`latitude`, `longitude`).

### 9. KYC & Document Uploads
- **`POST /api/v1/kyc/upload`**: Upload customer KYC file (`customer_id`, `document_type`, `file` max 5MB).
- **`GET /api/v1/kyc/documents/{id}`**: Secure document download/stream.

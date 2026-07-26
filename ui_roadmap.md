# UI / Design System Roadmap - TG Microfinance ERP

## Overview
**TG Microfinance ERP** features a high-end, responsive design system powered by **Bootstrap 5**, **Google Fonts (Outfit & Inter)**, **Bootstrap Icons**, Vanilla CSS micro-animations, and custom Blade UI components.

---

## Design System Foundations

### 1. Typography
- **Primary Font**: `'Outfit', sans-serif` (Modern, clean, legible for financial numbers).
- **Secondary Font**: `'Inter', sans-serif` (Dense tabular data & reports).

### 2. Color Palette

| Category | Hex Code | Purpose |
| :--- | :--- | :--- |
| **Primary Brand** | `#0d6efd` / `#0f172a` | Public brand highlights & Admin Sidebar background |
| **Success / Credit** | `#10b981` | Positive balances, active loans, paid repayments |
| **Warning / Pending** | `#f59e0b` | Pending approvals, partial payments, warning badges |
| **Danger / Risk** | `#ef4444` | Defaulted loans, overdue schedules, rejected requests |
| **Info / Status** | `#06b6d4` | Branch tags, info toasts, report summaries |
| **Neutral Surface** | `#f8f9fa` / `#f1f5f9` | Page background canvas |

---

## Layout Templates & Page Specifications

### 1. Public Corporate Website (`resources/views/layouts/public.blade.php`)
- **Top Navigation Bar**: Brand logo, navigation links (`Home`, `About`, `Services`, `Loan Products`, `Savings Products`, `Branches`, `Gallery`, `Downloads`, `FAQ`, `Career`, `Contact`), and action buttons (`Apply Loan`, `Staff Login`).
- **Footer**: Company overview, quick links, branch locator links, legal disclaimers, copyright notice.
- **Pages**:
  1. `home.blade.php`: Hero banner, Rate calculator widget, product highlights, customer testimonials.
  2. `about.blade.php`: Corporate mission, vision, executive leadership, company history.
  3. `services.blade.php`: Service offerings (Micro-loans, Group savings, Business advisory).
  4. `loan-products.blade.php`: Comparative loan matrix, eligibility criteria, interest rates.
  5. `savings-products.blade.php`: High-yield savings accounts, fixed deposits, passbook terms.
  6. `branches.blade.php`: Interactive branch list, office addresses, phone numbers, branch manager details.
  7. `gallery.blade.php`: Community outreach events, CSR activities, branch photos.
  8. `downloads.blade.php`: Downloadable application forms, policy documents, annual reports.
  9. `faq.blade.php`: Accordion FAQ on eligibility, repayments, interest calculations.
  10. `career.blade.php`: Job openings list, applicant requirements, submission form.
  11. `contact.blade.php`: Head office location map, contact numbers, general inquiry form.
  12. `apply-loan.blade.php`: Interactive public loan application submission request form.

---

### 2. Admin ERP Dashboard (`resources/views/layouts/admin.blade.php`)
- **Sidebar**: Sticky dark navigation bar featuring 11 module links with active state indicator.
- **Topbar**: Real-time branch context badge, user profile pill, role indicator (`Super Admin`, `Branch Manager`, etc.), and logout dropdown.
- **Main Canvas**: Alert banners, page breadcrumbs, data filter toolbars, responsive tables.

---

## Reusable Blade UI Component Specifications

- `<x-stat-box>`: Metrics card with icon, title, value, and trend percentage.
- `<x-data-table>`: Styled table with search bar, status badges, action dropdowns, and pagination controls.
- `<x-status-badge>`: Standardized role/status badge (`Active`, `Pending`, `Approved`, `Disbursed`, `Closed`, `Overdue`).
- `<x-modal>`: Reusable modal dialog for approvals, confirmation prompts, and transaction popups.
- `<x-alert>`: Dismissible success, error, warning, and audit notice banners.

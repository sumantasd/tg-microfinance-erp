<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoanAccount;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    /**
     * Generate unique, non-duplicating invoice number.
     * Format: INV-{YEAR}-{SEQUENCE} (e.g. INV-2026-000001)
     */
    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        
        // Lock max ID in invoices table safely
        $maxId = DB::table('invoices')->max('id') ?? 0;
        $nextSeq = str_pad($maxId + 1, 6, '0', STR_PAD_LEFT);
        
        $candidate = "INV-{$year}-{$nextSeq}";

        // Ensure uniqueness fallback
        while (Invoice::where('invoice_number', $candidate)->exists()) {
            $maxId++;
            $nextSeq = str_pad($maxId + 1, 6, '0', STR_PAD_LEFT);
            $candidate = "INV-{$year}-{$nextSeq}";
        }

        return $candidate;
    }

    /**
     * Generate Invoice for a Direct Product Sale.
     */
    public function generateInvoiceForDirectSale(Sale $sale): Invoice
    {
        if ($sale->invoice) {
            return $sale->invoice;
        }

        return DB::transaction(function () use ($sale) {
            $invoiceNumber = $this->generateInvoiceNumber();

            $createdBy = $sale->created_by
                ?? auth()->id()
                ?? User::where('company_id', $sale->company_id)->value('id')
                ?? User::value('id');

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_type' => 'direct_sale',
                'company_id' => $sale->company_id,
                'branch_id' => $sale->branch_id,
                'customer_id' => $sale->customer_id,
                'invoiceable_type' => Sale::class,
                'invoiceable_id' => $sale->id,
                'invoice_date' => $sale->sale_date ?? now()->toDateString(),
                'due_date' => $sale->sale_date ?? now()->toDateString(),
                'subtotal' => $sale->subtotal,
                'discount_amount' => $sale->discount_amount,
                'tax_amount' => $sale->tax_amount,
                'grand_total' => $sale->total_amount,
                'paid_amount' => $sale->paid_amount,
                'balance_due' => $sale->balance_amount,
                'status' => ($sale->balance_amount <= 0) ? 'paid' : 'partially_paid',
                'payment_method' => $sale->payment_method,
                'reference_number' => $sale->sale_number,
                'notes' => $sale->remarks ?? 'Direct product sale transaction.',
                'terms' => 'Thank you for your business. Payment received with thanks.',
                'created_by' => $createdBy,
            ]);

            foreach ($sale->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'item_name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount,
                    'tax_amount' => $item->tax,
                    'line_total' => $item->total_amount,
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Generate Invoice for a Product Loan Fulfillment.
     */
    public function generateInvoiceForProductLoan(LoanAccount $loanAccount): Invoice
    {
        if ($loanAccount->invoice) {
            return $loanAccount->invoice;
        }

        return DB::transaction(function () use ($loanAccount) {
            $application = $loanAccount->application;
            if (!$application || $application->products->count() === 0) {
                throw ValidationException::withMessages(['loan_account' => 'Cannot generate product loan invoice: No product items found.']);
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            $totalProductPrice = (float) $loanAccount->product_price_amount;
            if ($totalProductPrice <= 0) {
                $totalProductPrice = (float) $application->products->sum('total_value');
            }

            $downPayment = (float) $loanAccount->down_payment_amount;
            $financedAmount = (float) $loanAccount->sanctioned_amount;

            $createdBy = auth()->id()
                ?? $loanAccount->created_by
                ?? $loanAccount->application?->created_by
                ?? User::where('company_id', $loanAccount->company_id)->value('id')
                ?? User::value('id');

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_type' => 'product_loan',
                'company_id' => $loanAccount->company_id,
                'branch_id' => $loanAccount->branch_id,
                'customer_id' => $loanAccount->customer_id,
                'invoiceable_type' => LoanAccount::class,
                'invoiceable_id' => $loanAccount->id,
                'invoice_date' => $loanAccount->disbursement_date ?? now()->toDateString(),
                'due_date' => $loanAccount->maturity_date ?? null,
                'subtotal' => $totalProductPrice,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => $totalProductPrice,
                'paid_amount' => $downPayment,
                'balance_due' => $financedAmount,
                'status' => 'issued',
                'payment_method' => 'product_loan_financing',
                'reference_number' => $loanAccount->loan_number,
                'notes' => "Product Loan Invoice for Loan #{$loanAccount->loan_number}. Down payment: ₹" . number_format($downPayment, 2) . ", Financed: ₹" . number_format($financedAmount, 2) . ".",
                'terms' => 'Product issued under Product Loan Financing agreement. Repayable in monthly EMI installments as per loan schedule.',
                'created_by' => $createdBy,
            ]);

            foreach ($application->products as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'item_name' => $item->product_name_snapshot ?? ($item->product->name ?? 'Product Item'),
                    'sku' => $item->product->sku ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price_snapshot,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'line_total' => round($item->quantity * $item->unit_price_snapshot, 2),
                    'meta' => [
                        'loan_number' => $loanAccount->loan_number,
                        'product_price' => $totalProductPrice,
                        'down_payment' => $downPayment,
                        'financed_amount' => $financedAmount,
                    ],
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Cancel / Void an Invoice safely.
     */
    public function cancelInvoice(Invoice $invoice, string $reason, User $user): Invoice
    {
        if ($invoice->isCancelled()) {
            throw ValidationException::withMessages(['invoice' => 'Invoice is already cancelled.']);
        }

        return DB::transaction(function () use ($invoice, $reason, $user) {
            $invoice->update([
                'status' => 'cancelled',
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $invoice;
        });
    }
}

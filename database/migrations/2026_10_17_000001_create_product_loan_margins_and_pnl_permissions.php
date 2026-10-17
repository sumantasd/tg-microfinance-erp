<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create product_loan_margins Table
        if (!Schema::hasTable('product_loan_margins')) {
            Schema::create('product_loan_margins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_account_id')->unique()->constrained('loan_accounts')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->decimal('cost_price_total', 12, 2)->default(0.00);
                $table->decimal('selling_price_total', 12, 2)->default(0.00);
                $table->decimal('margin_amount', 12, 2)->default(0.00);
                $table->enum('status', ['pending', 'finalized', 'cancelled', 'reversed'])->default('pending');
                $table->timestamp('finalized_at')->nullable();
                $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reversed_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['branch_id', 'status']);
            });
        }

        // 2. Register & Seed Granular Profit & Loss Permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $pnlPermissions = [
            'profit_loss.view',
            'profit_loss.export',
        ];

        foreach ($pnlPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign strictly to Authorized Admin roles (Super Admin, Admin, Company Admin)
        // Explicitly NOT assigned to Branch Manager or any operational roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($pnlPermissions);
        }

        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo($pnlPermissions);
        }

        $companyAdmin = Role::where('name', 'Company Admin')->first();
        if ($companyAdmin) {
            $companyAdmin->givePermissionTo($pnlPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_loan_margins');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['profit_loss.view', 'profit_loss.export'])->delete();
    }
};

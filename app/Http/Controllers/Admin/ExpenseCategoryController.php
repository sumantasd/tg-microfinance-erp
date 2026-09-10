<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExpenseCategoryRequest;
use App\Models\ChartOfAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? 1;

        $query = ExpenseCategory::where('company_id', $companyId)
            ->with(['parent', 'children', 'chartOfAccount', 'expenses']);

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('category_name', 'like', "%{$search}%")
                  ->orWhere('category_code', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('category_name')->paginate(20)->withQueryString();
        $parentCategories = ExpenseCategory::where('company_id', $companyId)->whereNull('parent_id')->get();
        $chartOfAccounts = ChartOfAccount::where('company_id', $companyId)
            ->whereIn('account_type', ['expense', 'asset'])
            ->orderBy('account_name')
            ->get();

        return view('admin.expenses.categories.index', compact('categories', 'parentCategories', 'chartOfAccounts'));
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['company_id'] = $user->company_id ?? 1;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['is_active'] = $request->has('is_active') ? true : false;

        $category = ExpenseCategory::create($data);

        return redirect()->route('admin.expenses.categories.index')
            ->with('success', "Expense Category '{$category->category_name}' created successfully.");
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['updated_by'] = $user->id;
        $data['is_active'] = $request->has('is_active') ? true : false;

        $category->update($data);

        return redirect()->route('admin.expenses.categories.index')
            ->with('success', "Expense Category '{$category->category_name}' updated successfully.");
    }

    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        if ($category->expenses()->count() > 0) {
            return redirect()->back()
                ->with('error', "Category '{$category->category_name}' cannot be deleted because it is already used in {$category->expenses()->count()} expense entries.");
        }

        if ($category->children()->count() > 0) {
            return redirect()->back()
                ->with('error', "Category '{$category->category_name}' cannot be deleted because it has subcategories.");
        }

        $category->delete();

        return redirect()->route('admin.expenses.categories.index')
            ->with('success', "Category '{$category->category_name}' deleted successfully.");
    }
}

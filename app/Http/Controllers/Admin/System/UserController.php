<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreUserRequest;
use App\Http\Requests\System\UpdateUserRequest;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected UserService $userService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status', 'role']);
        $users = $this->userRepository->getPaginatedUsers($filters, 10);
        $roles = Role::all();

        return view('admin.system.users.index', compact('users', 'roles', 'filters'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $roles = Role::all();
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $companies = \App\Models\Company::where('is_active', true)->orderBy('name')->get();
        $unlinkedEmployees = \App\Models\Employee::whereNull('user_id')
            ->where('status', 'active')
            ->with(['branch', 'designation'])
            ->orderBy('first_name')
            ->get();

        return view('admin.system.users.create', compact('roles', 'branches', 'companies', 'unlinkedEmployees'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->createUser($request->validated());

        return redirect()->route('admin.system.users.index')->with('success', 'User account created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $roles = Role::all();
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $companies = \App\Models\Company::where('is_active', true)->orderBy('name')->get();
        $availableEmployees = \App\Models\Employee::where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->where('status', 'active')
            ->with(['branch', 'designation'])
            ->orderBy('first_name')
            ->get();
        $linkedEmployee = $user->resolveEmployeeProfile();

        return view('admin.system.users.edit', compact('user', 'roles', 'branches', 'companies', 'availableEmployees', 'linkedEmployee'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()->route('admin.system.users.index')->with('success', 'User account updated successfully.');
    }

    public function linkEmployeeProfile(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $action = $request->input('action');

        if ($action === 'unlink') {
            $this->userService->unlinkEmployeeFromUser($user);
            return back()->with('success', 'Employee profile successfully unlinked from user account.');
        }

        if ($action === 'create') {
            $emp = $this->userService->createEmployeeProfileForUser($user);
            return back()->with('success', "New employee profile ({$emp->employee_code}) successfully created and linked.");
        }

        if ($action === 'link') {
            $request->validate([
                'employee_record_id' => 'required|integer|exists:employees,id',
            ]);
            $emp = \App\Models\Employee::findOrFail($request->input('employee_record_id'));
            $this->userService->linkEmployeeToUser($user, $emp);
            return back()->with('success', "Employee profile ({$emp->employee_code}) successfully linked to user account.");
        }

        return back()->with('error', 'Invalid employee mapping action requested.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate(['status' => 'required|in:active,inactive,suspended,locked']);
        $this->userService->toggleUserStatus($user, $request->status);

        return back()->with('success', 'User status updated to ' . strtoupper($request->status));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user);

        return redirect()->route('admin.system.users.index')->with('success', 'User account soft deleted successfully.');
    }
}

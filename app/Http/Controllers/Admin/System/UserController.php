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
        return view('admin.system.users.create', compact('roles'));
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
        return view('admin.system.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()->route('admin.system.users.index')->with('success', 'User account updated successfully.');
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

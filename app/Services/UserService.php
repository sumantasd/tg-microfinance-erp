<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function createUser(array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $roleName = $data['role'] ?? null;
        unset($data['role']);

        $user = $this->userRepository->create($data);

        if ($roleName) {
            $user->syncRoles([$roleName]);
        }

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['updated_by'] = Auth::id();

        $roleName = $data['role'] ?? null;
        unset($data['role']);

        $updatedUser = $this->userRepository->update($user, $data);

        if ($roleName) {
            $updatedUser->syncRoles([$roleName]);
        }

        return $updatedUser;
    }

    public function toggleUserStatus(User $user, string $status): bool
    {
        return $this->userRepository->toggleStatus($user, $status);
    }

    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}

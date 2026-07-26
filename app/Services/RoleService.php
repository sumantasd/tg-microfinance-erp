<?php

namespace App\Services;

use Spatie\Permission\Models\Role;

class RoleService
{
    public function createRole(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role;
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'],
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role;
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->name === 'Super Admin') {
            throw new \InvalidArgumentException('Super Admin role cannot be deleted.');
        }

        return $role->delete();
    }
}

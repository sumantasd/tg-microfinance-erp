<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('permissions.view');

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.system.permissions.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->authorize('permissions.assign');

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        Permission::create([
            'name' => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Permission node created successfully.');
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('permissions.assign');

        $permission->delete();

        return back()->with('success', 'Permission removed.');
    }
}

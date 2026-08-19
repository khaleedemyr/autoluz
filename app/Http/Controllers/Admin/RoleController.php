<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderByDesc('is_super')
            ->orderBy('name')
            ->get()
            ->map->toAdminArray()
            ->values();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissionCatalog' => AdminPermissions::catalog(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Role::query()->create([
            'name' => $data['name'],
            'slug' => Role::uniqueSlug($data['name']),
            'is_super' => false,
            'permissions' => $this->normalizedPermissions($data['permissions'] ?? []),
        ]);

        return back()->with('success', 'Role ditambahkan.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);

        $role->name = $data['name'];
        $role->slug = Role::uniqueSlug($data['name'], $role->id);

        if (! $role->is_super) {
            $role->permissions = $this->normalizedPermissions($data['permissions'] ?? []);
        }

        $role->save();

        return back()->with('success', 'Role disimpan.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_super) {
            return back()->withErrors(['role' => 'Role Super Admin tidak bisa dihapus.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Role masih dipakai user. Pindahkan dulu ke role lain.']);
        }

        $role->delete();

        return back()->with('success', 'Role dihapus.');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AdminPermissions::keys())],
        ]);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function normalizedPermissions(array $permissions): array
    {
        $allowed = AdminPermissions::keys();
        $clean = array_values(array_intersect($allowed, $permissions));

        if (! in_array('dashboard', $clean, true)) {
            array_unshift($clean, 'dashboard');
        }

        return $clean;
    }
}

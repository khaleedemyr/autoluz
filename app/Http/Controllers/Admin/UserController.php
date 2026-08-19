<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('role')
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->canAccessAdmin(),
                'role_id' => $user->role_id,
                'role_name' => $user->role?->name,
                'created_at' => optional($user->created_at)?->format('Y-m-d H:i'),
            ]);

        $roles = Role::query()
            ->orderByDesc('is_super')
            ->orderBy('name')
            ->get(['id', 'name', 'is_super']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'] ?? null,
            'is_admin' => ! empty($data['role_id']),
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $nextRoleId = $data['role_id'] ?? null;
        $nextRole = $nextRoleId ? Role::query()->find($nextRoleId) : null;
        $willBeStaff = (bool) $nextRoleId;
        $willBeSuper = (bool) ($nextRole?->is_super);

        if ($user->canAccessAdmin() && ! $willBeStaff && $this->staffCount() <= 1) {
            return back()->withErrors(['role_id' => 'Minimal harus ada 1 akun admin.']);
        }

        if ($user->isSuperAdmin() && ! $willBeSuper && $this->superCount() <= 1) {
            return back()->withErrors(['role_id' => 'Minimal harus ada 1 Super Admin.']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role_id = $nextRoleId;
        $user->is_admin = $willBeStaff;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'User disimpan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun sendiri.']);
        }

        if ($user->canAccessAdmin() && $this->staffCount() <= 1) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus admin terakhir.']);
        }

        if ($user->isSuperAdmin() && $this->superCount() <= 1) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus Super Admin terakhir.']);
        }

        $user->delete();

        return back()->with('success', 'User dihapus.');
    }

    protected function staffCount(): int
    {
        return User::query()->where(function ($query) {
            $query->where('is_admin', true)->orWhereNotNull('role_id');
        })->count();
    }

    protected function superCount(): int
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('role', fn ($role) => $role->where('is_super', true))
                    ->orWhere(function ($inner) {
                        $inner->where('is_admin', true)->whereNull('role_id');
                    });
            })
            ->count();
    }
}

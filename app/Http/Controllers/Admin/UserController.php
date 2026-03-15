<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function allowedRoles(): array
    {
        return ['admin', 'user', 'staff', 'teacher'];
    }

    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('last_name', 'like', "%{$q}%")
                       ->orWhere('first_name', 'like', "%{$q}%")
                       ->orWhere('last_name_kana', 'like', "%{$q}%")
                       ->orWhere('first_name_kana', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.users.index', [
            'users' => $users,
            'q'     => $q,
            'roles' => $this->allowedRoles(),
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        if (!RolePermissionService::canUser($request->user(), 'admin_users', 'update')) {
            abort(403);
        }

        // ✅ 自分自身の権限変更は禁止（事故防止）
        if ((int)$request->user()->id === (int)$user->id) {
            return back()->with('error', '自分自身の権限はこの画面から変更できません。');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', $this->allowedRoles())],
            'hourly_wage' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $user->role = $validated['role'];
        $user->hourly_wage = $validated['hourly_wage'] ?? null;
        $user->save();

        return back()->with('success', '権限・時給を更新しました。');
    }
}

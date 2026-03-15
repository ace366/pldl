<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\RolePermission;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    private const REGISTER_GATE_SETTING_KEY = 'registration_gate_password';

    public function index(Request $request)
    {
        $roles = RolePermissionService::roles();
        $features = RolePermissionService::features();
        $actions = RolePermissionService::actions();

        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role] = RolePermissionService::matrixForRole($role);
        }

        return view('admin.permissions.index', [
            'roles' => $roles,
            'features' => $features,
            'actions' => $actions,
            'matrix' => $matrix,
            'registrationGatePassword' => AppSetting::getValue(self::REGISTER_GATE_SETTING_KEY, 'pldl-register'),
        ]);
    }

    public function update(Request $request)
    {
        $roles = RolePermissionService::roles();
        $features = RolePermissionService::features();
        $actions = RolePermissionService::actions();

        $validated = $request->validate([
            'registration_gate_password' => ['required', 'string', 'max:255'],
        ]);

        $registrationGatePassword = trim((string) ($validated['registration_gate_password'] ?? ''));
        if ($registrationGatePassword === '') {
            return back()->withErrors([
                'registration_gate_password' => '登録ロック用パスワードを入力してください。',
            ])->withInput();
        }

        AppSetting::setValue(self::REGISTER_GATE_SETTING_KEY, $registrationGatePassword);

        $input = $request->input('permissions', []);

        foreach ($roles as $role) {
            foreach ($features as $featureKey => $label) {
                $data = [
                    'can_view' => false,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];

                foreach ($actions as $action) {
                    $data["can_{$action}"] = !empty($input[$role][$featureKey][$action]);
                }

                RolePermission::updateOrCreate(
                    [
                        'role' => $role,
                        'feature' => $featureKey,
                    ],
                    $data
                );
            }
        }

        return back()->with('success', '権限設定を保存しました。');
    }
}

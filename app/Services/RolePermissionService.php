<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;

class RolePermissionService
{
    public static function roles(): array
    {
        return ['admin', 'staff', 'teacher', 'user'];
    }

    public static function actions(): array
    {
        return ['view', 'create', 'update', 'delete'];
    }

    public static function features(): array
    {
        return [
            'my_qr' => 'マイQR',
            'today_attendance' => '今日の勤怠（打刻）',
            'attendance_qr' => '出退勤（読み取り）',
            'child_qr_scan' => '児童QR（読み取り）',
            'shift_day' => 'シフト（日別）',
            'shift_month' => 'シフト（月表示）',
            'attendance_month' => '勤怠（月次）',
            'audit_logs' => '監査ログ',
            'closings' => '月次締め',
            'attendance_intents' => '参加予定',
            'schools_master' => '学校マスタ',
            'bases_master' => '拠点マスタ',
            'children_index' => '児童管理（一覧）',
            'guardians_index' => '保護者管理',
            'admin_users' => '管理者管理',
        ];
    }

    public static function canUser(?User $user, string $feature, string $action = 'view'): bool
    {
        if (!$user) return false;
        $role = (string)($user->role ?? 'user');
        return self::canRole($role, $feature, $action);
    }

    public static function canRole(string $role, string $feature, string $action = 'view'): bool
    {
        if ($role === '') return false;
        $matrix = self::matrixForRole($role);
        return (bool)($matrix[$feature][$action] ?? false);
    }

    public static function matrixForRole(string $role): array
    {
        $rows = RolePermission::query()
            ->where('role', $role)
            ->get();

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row->feature] = [
                'view' => (bool)$row->can_view,
                'create' => (bool)$row->can_create,
                'update' => (bool)$row->can_update,
                'delete' => (bool)$row->can_delete,
            ];
        }

        return $matrix;
    }
}

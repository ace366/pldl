<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $roles = ['admin', 'staff', 'teacher', 'user'];
        $features = [
            'my_qr',
            'today_attendance',
            'attendance_qr',
            'child_qr_scan',
            'shift_day',
            'shift_month',
            'attendance_month',
            'audit_logs',
            'closings',
            'attendance_intents',
            'schools_master',
            'bases_master',
            'children_index',
            'guardians_index',
            'admin_users',
        ];

        $now = now();
        foreach ($roles as $role) {
            foreach ($features as $feature) {
                $exists = DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('feature', $feature)
                    ->exists();
                if ($exists) continue;

                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'feature' => $feature,
                    'can_view' => false,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op: keep data
    }
};

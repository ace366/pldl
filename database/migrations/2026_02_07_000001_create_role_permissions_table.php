<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50);
            $table->string('feature', 100);
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['role', 'feature']);
        });

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
        $rows = [];
        foreach ($roles as $role) {
            foreach ($features as $feature) {
                $isAdmin = ($role === 'admin');
                $rows[] = [
                    'role' => $role,
                    'feature' => $feature,
                    'can_view' => $isAdmin ? true : false,
                    'can_create' => $isAdmin ? true : false,
                    'can_update' => $isAdmin ? true : false,
                    'can_delete' => $isAdmin ? true : false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // 既存運用の想定：staff/teacher は一部表示のみ許可
        foreach (['staff', 'teacher'] as $role) {
            foreach (['my_qr', 'today_attendance', 'attendance_qr', 'attendance_intents', 'children_index'] as $feature) {
                foreach ($rows as &$row) {
                    if ($row['role'] === $role && $row['feature'] === $feature) {
                        $row['can_view'] = true;
                        if ($feature === 'children_index') {
                            $row['can_create'] = true; // TEL票追加を想定
                        }
                    }
                }
                unset($row);
            }
        }

        DB::table('role_permissions')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};

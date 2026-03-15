<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role',
        'feature',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];
}

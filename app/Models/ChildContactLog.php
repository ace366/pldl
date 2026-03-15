<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildContactLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'child_id',
        'guardian_id',
        'created_by',
        'title',
        'body',
        'contact_date',
        'channel',
    ];

    protected $casts = [
        'contact_date' => 'date',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

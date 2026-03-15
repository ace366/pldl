<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingTaxTable extends Model
{
    protected $fillable = [
        'year',
        'pay_type',
        'column_type',
        'dep_count',
        'min_amount',
        'max_amount',
        'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'dep_count' => 'integer',
            'min_amount' => 'integer',
            'max_amount' => 'integer',
            'tax_amount' => 'integer',
        ];
    }
}


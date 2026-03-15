<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPayment extends Model
{
    protected $fillable = [
        'user_id',
        'year_month',
        'tax_year',
        'pay_type',
        'column_type',
        'dep_count',
        'social_insurance_amount',
        'gross_pay',
        'taxable_amount',
        'withholding_tax',
        'net_pay',
        'closing_date',
        'payment_date',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_year' => 'integer',
            'dep_count' => 'integer',
            'social_insurance_amount' => 'integer',
            'gross_pay' => 'integer',
            'taxable_amount' => 'integer',
            'withholding_tax' => 'integer',
            'net_pay' => 'integer',
            'closing_date' => 'date',
            'payment_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


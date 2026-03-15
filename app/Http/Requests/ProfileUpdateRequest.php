<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $bankCode = $this->input('bank_code');
        $bankBranchCode = $this->input('bank_branch_code');
        $bankAccountNumber = $this->input('bank_account_number');

        $this->merge([
            'phone' => is_string($phone) ? preg_replace('/\\D+/', '', $phone) : $phone,
            'bank_code' => is_string($bankCode) ? preg_replace('/\\D+/', '', $bankCode) : $bankCode,
            'bank_branch_code' => is_string($bankBranchCode) ? preg_replace('/\\D+/', '', $bankBranchCode) : $bankBranchCode,
            'bank_account_number' => is_string($bankAccountNumber) ? preg_replace('/\\D+/', '', $bankAccountNumber) : $bankAccountNumber,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'regex:/^\\d{10,11}$/'],
            'bank_code' => ['nullable', 'regex:/^\\d{4}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_branch_code' => ['nullable', 'regex:/^\\d{3}$/'],
            'bank_branch_name' => ['nullable', 'string', 'max:100'],
            'bank_account_type' => ['nullable', Rule::in(['ordinary', 'current'])],
            'bank_account_number' => ['nullable', 'regex:/^\\d{1,10}$/'],
        ];
    }
}

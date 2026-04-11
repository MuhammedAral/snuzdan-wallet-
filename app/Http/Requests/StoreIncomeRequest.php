<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'income_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Tutar girilmesi zorunludur.',
            'amount.gt' => 'Tutar sıfırdan büyük olmalıdır.',
            'currency.required' => 'Para birimi seçimi zorunludur.',
            'category_id.required' => 'Kategori seçimi zorunludur.',
            'category_id.exists' => 'Geçersiz kategori seçtiniz.',
            'income_date.required' => 'Gelir tarihi zorunludur.',
        ];
    }
}

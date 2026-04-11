<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreTradeRequest — Yatırım İşlemi Validasyonu
 *
 * 2-of-3 logic: quantity, unit_price, total_amount'tan en az 2'si dolu olmalı.
 * Hata mesajları Türkçe.
 *
 * @see TASKS.md — Görev M-14
 */
class StoreTradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_id'         => 'required|uuid|exists:assets,id',
            'side'             => 'required|in:BUY,SELL',
            'quantity'         => 'nullable|numeric|min:0.000001',
            'unit_price'       => 'nullable|numeric|min:0.000001',
            'total_amount'     => 'nullable|numeric|min:0.01',
            'commission'       => 'nullable|numeric|min:0',
            'fx_rate_to_base'  => 'nullable|numeric|min:0',
            'note'             => 'nullable|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
        ];
    }

    /**
     * Custom validation: quantity, unit_price, total_amount'tan en az 2'si dolu olmalı.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quantity    = $this->input('quantity');
            $unitPrice   = $this->input('unit_price');
            $totalAmount = $this->input('total_amount');

            $filledCount = 0;
            if ($quantity !== null && $quantity > 0) $filledCount++;
            if ($unitPrice !== null && $unitPrice > 0) $filledCount++;
            if ($totalAmount !== null && $totalAmount > 0) $filledCount++;

            if ($filledCount < 2) {
                $validator->errors()->add(
                    'quantity',
                    'Adet, birim fiyat ve toplam tutar alanlarından en az 2 tanesi doldurulmalıdır.'
                );
            }
        });
    }

    /**
     * Türkçe hata mesajları.
     */
    public function messages(): array
    {
        return [
            'asset_id.required'         => 'Varlık seçimi zorunludur.',
            'asset_id.uuid'             => 'Geçersiz varlık formatı.',
            'asset_id.exists'           => 'Seçilen varlık bulunamadı.',
            'side.required'             => 'İşlem yönü (AL/SAT) seçilmelidir.',
            'side.in'                   => 'İşlem yönü BUY veya SELL olmalıdır.',
            'quantity.numeric'          => 'Adet sayısal bir değer olmalıdır.',
            'quantity.min'              => 'Adet en az 0.000001 olmalıdır.',
            'unit_price.numeric'        => 'Birim fiyat sayısal bir değer olmalıdır.',
            'unit_price.min'            => 'Birim fiyat en az 0.000001 olmalıdır.',
            'total_amount.numeric'      => 'Toplam tutar sayısal bir değer olmalıdır.',
            'total_amount.min'          => 'Toplam tutar en az 0.01 olmalıdır.',
            'commission.numeric'        => 'Komisyon sayısal bir değer olmalıdır.',
            'commission.min'            => 'Komisyon negatif olamaz.',
            'note.max'                  => 'Not en fazla 500 karakter olabilir.',
            'transaction_date.required' => 'İşlem tarihi zorunludur.',
            'transaction_date.date'     => 'Geçerli bir tarih giriniz.',
            'transaction_date.before_or_equal' => 'İşlem tarihi bugünden ileri bir tarih olamaz.',
        ];
    }
}

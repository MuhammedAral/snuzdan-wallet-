<?php

namespace App\Services;

use App\Models\InvestmentTransaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * InvestmentService — Yatırım İşlem Servisi
 *
 * Trade girişi, void (iptal), listeleme ve otomatik hesaplama (2-of-3 logic).
 * Append-only: Sadece INSERT yapılır, DELETE/UPDATE yasak.
 *
 * @see ARCHITECTURE.md §3 — Key Service Responsibilities
 */
class InvestmentService
{
    /**
     * Yeni yatırım işlemi oluştur (sadece INSERT).
     *
     * 2-of-3 Logic: quantity, unit_price, total_amount alanlarından
     * en az 2'si dolu olmalı. Eksik olan otomatik hesaplanır.
     *
     * @param array $data  İşlem verileri
     * @param User  $user  İşlemi yapan kullanıcı
     * @return InvestmentTransaction
     *
     * @throws InvalidArgumentException Validasyon hatası durumunda
     */
    public function store(array $data, User $user): InvestmentTransaction
    {
        // 2-of-3 auto-calc logic
        $data = $this->autoCalculateFields($data);

        // Tolerans kontrolü: |total_amount - quantity * unit_price| < 0.01
        $calculated = $data['quantity'] * $data['unit_price'];
        if (abs($data['total_amount'] - $calculated) >= 0.01) {
            throw new InvalidArgumentException(
                "total_amount ({$data['total_amount']}) ile quantity × unit_price ({$calculated}) uyumsuz."
            );
        }

        return InvestmentTransaction::create([
            'workspace_id'      => $user->current_workspace_id,
            'created_by_user_id' => $user->id,
            'asset_id'          => $data['asset_id'],
            'side'              => $data['side'],
            'quantity'          => $data['quantity'],
            'unit_price'        => $data['unit_price'],
            'total_amount'      => $data['total_amount'],
            'commission'        => $data['commission'] ?? 0,
            'fx_rate_to_base'   => $data['fx_rate_to_base'] ?? 1.0,
            'note'              => $data['note'] ?? null,
            'transaction_date'  => $data['transaction_date'] ?? now(),
        ]);
    }

    /**
     * Yatırım işlemini iptal et (void).
     *
     * Append-only: Satırı silmez, is_void = true yapar.
     *
     * @param string $id     İptal edilecek işlem ID'si
     * @param string $reason İptal sebebi
     * @param User   $user   İptal eden kullanıcı
     * @return InvestmentTransaction
     */
    public function void(string $id, string $reason, User $user): InvestmentTransaction
    {
        $transaction = InvestmentTransaction::where('id', $id)
            ->where('workspace_id', $user->current_workspace_id)
            ->where('is_void', false)
            ->firstOrFail();

        $transaction->update([
            'is_void'     => true,
            'void_reason' => $reason,
            'voided_at'   => now(),
        ]);

        Log::info("Yatırım işlemi iptal edildi", [
            'transaction_id' => $id,
            'user_id'        => $user->id,
            'reason'         => $reason,
        ]);

        return $transaction->fresh();
    }

    /**
     * Kullanıcının yatırım işlemlerini listele (filtrelenebilir, paginated).
     *
     * @param User  $user    Kullanıcı
     * @param array $filters Filtreler: asset_class, asset_id, side, date_from, date_to
     * @return LengthAwarePaginator
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = InvestmentTransaction::where('workspace_id', $user->current_workspace_id)
            ->with('asset')
            ->orderByDesc('transaction_date');

        // Asset class filtresi (CRYPTO, STOCK, FX)
        if (!empty($filters['asset_class'])) {
            $query->whereHas('asset', function ($q) use ($filters) {
                $q->where('asset_class', $filters['asset_class']);
            });
        }

        // Belirli asset filtresi
        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        // BUY veya SELL filtresi
        if (!empty($filters['side'])) {
            $query->where('side', $filters['side']);
        }

        // Tarih aralığı filtresi
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->paginate(20);
    }

    /**
     * 2-of-3 otomatik hesaplama.
     *
     * 3 alandan (quantity, unit_price, total_amount) en az 2'si dolu olmalı.
     * Eksik olan otomatik hesaplanır:
     * - quantity + unit_price → total_amount = quantity × unit_price
     * - quantity + total_amount → unit_price = total_amount / quantity
     * - unit_price + total_amount → quantity = total_amount / unit_price
     *
     * @param array $data İşlem verileri
     * @return array      Hesaplanmış veriler
     *
     * @throws InvalidArgumentException 2'den az alan doluysa
     */
    private function autoCalculateFields(array $data): array
    {
        $hasQuantity    = isset($data['quantity']) && $data['quantity'] > 0;
        $hasUnitPrice   = isset($data['unit_price']) && $data['unit_price'] > 0;
        $hasTotalAmount = isset($data['total_amount']) && $data['total_amount'] > 0;

        $filledCount = ($hasQuantity ? 1 : 0) + ($hasUnitPrice ? 1 : 0) + ($hasTotalAmount ? 1 : 0);

        if ($filledCount < 2) {
            throw new InvalidArgumentException(
                'quantity, unit_price ve total_amount alanlarından en az 2 tanesi dolu olmalıdır.'
            );
        }

        // Eksik alanı hesapla
        if (!$hasTotalAmount) {
            $data['total_amount'] = $data['quantity'] * $data['unit_price'];
        } elseif (!$hasUnitPrice) {
            $data['unit_price'] = $data['total_amount'] / $data['quantity'];
        } elseif (!$hasQuantity) {
            $data['quantity'] = $data['total_amount'] / $data['unit_price'];
        }

        return $data;
    }
}

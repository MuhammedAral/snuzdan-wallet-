<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\InvestmentTransaction;
use App\Models\PriceSnapshot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PortfolioService — Portföy Analiz Servisi
 *
 * Net pozisyonlar, FIFO realized PnL, unrealized PnL ve portföy özeti.
 * Dashboard'daki NetWorthCard ve TopMoversCard bu servisi kullanır.
 *
 * @see ARCHITECTURE.md §3 — Key Service Responsibilities
 */
class PortfolioService
{
    private PriceService $priceService;

    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * Kullanıcının açık pozisyonlarını getir.
     *
     * v_positions VIEW'ını sorgular ve her pozisyon için anlık fiyat ekler.
     * Unrealized PnL = (anlık_fiyat - ortalama_maliyet) × net_quantity
     *
     * @param User $user Kullanıcı
     * @return Collection  Pozisyon listesi
     */
    public function getPositions(User $user): Collection
    {
        $positions = DB::table('v_positions')
            ->where('workspace_id', $user->current_workspace_id)
            ->where('net_quantity', '>', 0) // Sadece açık pozisyonlar
            ->get();

        if ($positions->isEmpty()) {
            return collect();
        }

        // N+1 Fix: Tüm asset'leri tek sorguda çek
        $assetIds = $positions->pluck('asset_id')->unique()->values()->all();
        $assetsMap = Asset::whereIn('id', $assetIds)
            ->get()
            ->keyBy('id');

        // N+1 Fix: Tüm son fiyatları tek sorguda çek (her asset için en son snapshot)
        $priceMap = $this->getBulkLatestPrices($assetIds);

        return $positions->map(function ($position) use ($assetsMap, $priceMap) {
            $asset = $assetsMap->get($position->asset_id);
            $currentPrice = $priceMap[$position->asset_id] ?? 0;

            // Cache'te yoksa canlı çekmeyi dene (tek tek, zaten az sayıda)
            if ($currentPrice === 0 && $asset) {
                try {
                    $currentPrice = $this->priceService->getLatestPrice($asset);
                } catch (\Exception $e) {
                    Log::warning("Pozisyon fiyatı alınamadı: {$position->symbol}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Ortalama maliyet hesapla
            $avgCost = $position->net_quantity > 0
                ? $position->total_cost_base / $position->net_quantity
                : 0;

            // Unrealized PnL
            $unrealizedPnl = ($currentPrice - $avgCost) * $position->net_quantity;
            $unrealizedPnlPercent = $avgCost > 0
                ? (($currentPrice - $avgCost) / $avgCost) * 100
                : 0;

            return (object) [
                'workspace_id'            => $position->workspace_id,
                'asset_id'                => $position->asset_id,
                'asset_class'             => $position->asset_class,
                'symbol'                  => $position->symbol,
                'name'                    => $position->name,
                'net_quantity'            => (float) $position->net_quantity,
                'avg_cost'                => round($avgCost, 8),
                'current_price'           => $currentPrice,
                'total_cost_base'         => (float) $position->total_cost_base,
                'total_sell_proceeds_base' => (float) $position->total_sell_proceeds_base,
                'total_commission_base'   => (float) $position->total_commission_base,
                'unrealized_pnl'          => round($unrealizedPnl, 2),
                'unrealized_pnl_percent'  => round($unrealizedPnlPercent, 2),
                'first_trade'             => $position->first_trade,
                'last_trade'              => $position->last_trade,
                'trade_count'             => (int) $position->trade_count,
            ];
        });
    }

    /**
     * Birden fazla asset için son fiyatları tek sorguda çek.
     *
     * Önce Redis cache'e bakar, cache'te olmayan asset'ler için
     * DB'den tek bir DISTINCT ON sorgusu ile son snapshot'ları alır.
     *
     * @param array $assetIds
     * @return array  ['asset_id' => float price]
     */
    private function getBulkLatestPrices(array $assetIds): array
    {
        $priceMap = [];
        $missingIds = [];

        // 1. Önce Redis cache'e bak
        foreach ($assetIds as $id) {
            $cached = Cache::get("price:{$id}");
            if ($cached !== null) {
                $priceMap[$id] = (float) $cached;
            } else {
                $missingIds[] = $id;
            }
        }

        // 2. Cache'te olmayan asset'ler için DB sorgusu
        if (!empty($missingIds)) {
            $snapshots = \App\Models\PriceSnapshot::whereIn('asset_id', $missingIds)
                ->orderByDesc('fetched_at')
                ->get()
                ->unique('asset_id');

            foreach ($snapshots as $snap) {
                $priceMap[$snap->asset_id] = (float) $snap->price;
                Cache::put("price:{$snap->asset_id}", $snap->price, 60);
            }
        }

        return $priceMap;
    }

    /**
     * FIFO metoduyla realize edilmiş kar/zarar hesapla.
     *
     * BUY işlemlerini sırasıyla alır, SELL işlemlerinde FIFO tüketir.
     * Her satışta: PnL += (satış_fiyatı - alım_fiyatı) × satılan_miktar
     *
     * @param User   $user    Kullanıcı
     * @param string $assetId Varlık ID'si
     * @return float          FIFO realized PnL
     */
    public function getFifoPnL(User $user, string $assetId): float
    {
        // Tüm geçerli işlemleri tarih sırasıyla al
        $transactions = InvestmentTransaction::where('workspace_id', $user->current_workspace_id)
            ->where('asset_id', $assetId)
            ->where('is_void', false)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        // FIFO kuyruğu: [['quantity' => x, 'price' => y, 'fx_rate' => z], ...]
        $buyQueue = [];
        $realizedPnl = 0;

        foreach ($transactions as $tx) {
            if ($tx->side === 'BUY') {
                // Alım kuyruğuna ekle
                $buyQueue[] = [
                    'quantity' => $tx->quantity,
                    'price'    => $tx->unit_price,
                    'fx_rate'  => $tx->fx_rate_to_base,
                ];
            } elseif ($tx->side === 'SELL') {
                // FIFO tüketimi
                $remainingToSell = $tx->quantity;
                $sellPriceBase = $tx->unit_price * $tx->fx_rate_to_base;

                while ($remainingToSell > 0 && !empty($buyQueue)) {
                    $oldestBuy = &$buyQueue[0];
                    $buyPriceBase = $oldestBuy['price'] * $oldestBuy['fx_rate'];

                    if ($oldestBuy['quantity'] <= $remainingToSell) {
                        // Bu alım lotunu tamamen tüket
                        $soldQty = $oldestBuy['quantity'];
                        $realizedPnl += ($sellPriceBase - $buyPriceBase) * $soldQty;
                        $remainingToSell -= $soldQty;
                        array_shift($buyQueue); // İlk elemanı çıkar
                    } else {
                        // Kısmi tüketim
                        $soldQty = $remainingToSell;
                        $realizedPnl += ($sellPriceBase - $buyPriceBase) * $soldQty;
                        $oldestBuy['quantity'] -= $soldQty;
                        $remainingToSell = 0;
                    }
                }

                // Komisyon düşür
                $realizedPnl -= $tx->commission * $tx->fx_rate_to_base;
            }
        }

        return round($realizedPnl, 2);
    }

    /**
     * Portföy özet bilgilerini getir.
     *
     * Dashboard'daki NetWorthCard ve API endpoint'leri bu metodu kullanır.
     *
     * @param User $user Kullanıcı
     * @return array     Özet bilgiler
     */
    public function getSummary(User $user): array
    {
        $positions = $this->getPositions($user);

        $totalValue = $positions->sum(function ($pos) {
            return $pos->current_price * $pos->net_quantity;
        });

        $totalUnrealizedPnl = $positions->sum('unrealized_pnl');

        // N+1 Fix: Tüm asset işlemlerini tek sorguda çek, sonra PHP'de FIFO hesapla
        $assetIds = $positions->pluck('asset_id')->unique()->values()->all();
        $totalRealizedPnl = $this->getBulkFifoPnL($user, $assetIds);

        // Asset class dağılımı (%)
        $allocation = [];
        if ($totalValue > 0) {
            $grouped = $positions->groupBy('asset_class');
            foreach ($grouped as $class => $classPositions) {
                $classValue = $classPositions->sum(function ($pos) {
                    return $pos->current_price * $pos->net_quantity;
                });
                $allocation[$class] = round(($classValue / $totalValue) * 100, 2);
            }
        }

        return [
            'total_value'        => round($totalValue, 2),
            'total_unrealized'   => round($totalUnrealizedPnl, 2),
            'total_realized'     => round($totalRealizedPnl, 2),
            'allocation'         => $allocation,
            'position_count'     => $positions->count(),
        ];
    }

    /**
     * Birden fazla asset için FIFO PnL'i tek DB sorgusunda hesapla.
     *
     * Tüm workspace işlemlerini tek sorguda çeker ve PHP'de asset'e göre gruplayarak
     * FIFO hesaplamasını yapar. N ayrı sorgu yerine 1 sorgu.
     *
     * @param User  $user
     * @param array $assetIds
     * @return float Toplam realized PnL
     */
    private function getBulkFifoPnL(User $user, array $assetIds): float
    {
        if (empty($assetIds)) {
            return 0.0;
        }

        // Tek sorguda tüm işlemleri al
        $allTransactions = InvestmentTransaction::where('workspace_id', $user->current_workspace_id)
            ->whereIn('asset_id', $assetIds)
            ->where('is_void', false)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get()
            ->groupBy('asset_id');

        $totalRealizedPnl = 0;

        foreach ($allTransactions as $assetId => $transactions) {
            $buyQueue = [];
            $realizedPnl = 0;

            foreach ($transactions as $tx) {
                if ($tx->side === 'BUY') {
                    $buyQueue[] = [
                        'quantity' => $tx->quantity,
                        'price'    => $tx->unit_price,
                        'fx_rate'  => $tx->fx_rate_to_base,
                    ];
                } elseif ($tx->side === 'SELL') {
                    $remainingToSell = $tx->quantity;
                    $sellPriceBase = $tx->unit_price * $tx->fx_rate_to_base;

                    while ($remainingToSell > 0 && !empty($buyQueue)) {
                        $oldestBuy = &$buyQueue[0];
                        $buyPriceBase = $oldestBuy['price'] * $oldestBuy['fx_rate'];

                        if ($oldestBuy['quantity'] <= $remainingToSell) {
                            $soldQty = $oldestBuy['quantity'];
                            $realizedPnl += ($sellPriceBase - $buyPriceBase) * $soldQty;
                            $remainingToSell -= $soldQty;
                            array_shift($buyQueue);
                        } else {
                            $soldQty = $remainingToSell;
                            $realizedPnl += ($sellPriceBase - $buyPriceBase) * $soldQty;
                            $oldestBuy['quantity'] -= $soldQty;
                            $remainingToSell = 0;
                        }
                    }

                    $realizedPnl -= $tx->commission * $tx->fx_rate_to_base;
                }
            }

            $totalRealizedPnl += $realizedPnl;
        }

        return round($totalRealizedPnl, 2);
    }

    /**
     * Son 24 saatte en çok yüzde değişen pozisyonları getir.
     *
     * Dashboard'daki TopMoversCard bu metodu kullanır.
     *
     * @param User $user  Kullanıcı
     * @param int  $limit Kaç tane döndürülsün
     * @return array      Top movers listesi
     */
    public function getTopMovers(User $user, int $limit = 5): array
    {
        $positions = $this->getPositions($user);

        // Unrealized PnL yüzdesine göre sırala (mutlak değer — en çok hareket eden)
        $sorted = $positions->sortByDesc(function ($pos) {
            return abs($pos->unrealized_pnl_percent);
        })->take($limit);

        return $sorted->map(function ($pos) {
            return [
                'symbol'         => $pos->symbol,
                'name'           => $pos->name,
                'asset_class'    => $pos->asset_class,
                'current_price'  => $pos->current_price,
                'change_percent' => $pos->unrealized_pnl_percent,
                'pnl'            => $pos->unrealized_pnl,
            ];
        })->values()->toArray();
    }
}

<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Services\PortfolioService;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * PortfolioServiceTest — FIFO PnL Hesaplama Testleri
 *
 * Görev M-20: PortfolioService::getFifoPnL metodunun farklı senaryolarda
 * doğru sonuç verdiğini test eder.
 *
 * Senaryolar:
 * 1. Sadece BUY — realized PnL = 0
 * 2. BUY + SELL — FIFO sırasıyla kar/zarar
 * 3. Kısmi satım — ilk lot kısmen tüketilir
 * 4. Void edilmiş kayıtlar — hesaba dahil edilmez
 * 5. Çoklu BUY farklı fiyat + tek SELL
 * 6. Tüm pozisyon kapatma
 */
class PortfolioServiceTest extends TestCase
{
    use RefreshDatabase;

    private PortfolioService $portfolioService;
    private User $user;
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        // PriceService'i mock'la (test sırasında API çağrısı yapmasın)
        $mockPriceService = Mockery::mock(PriceService::class);
        $mockPriceService->shouldReceive('getLatestPrice')->andReturn(100.0);
        $this->portfolioService = new PortfolioService($mockPriceService);

        // Test kullanıcısı oluştur
        $this->user = User::factory()->create([
            'current_workspace_id' => null,
        ]);

        // Test asset'i oluştur
        $this->asset = Asset::create([
            'asset_class'   => 'CRYPTO',
            'symbol'        => 'BTC',
            'name'          => 'Bitcoin',
            'base_currency' => 'USD',
        ]);
    }

    /**
     * Senaryo 1: Sadece alış yapılmış, hiç satış yok.
     * Beklenen: Realized PnL = 0 (henüz realize edilmemiş)
     */
    public function test_only_buys_returns_zero_realized_pnl(): void
    {
        $this->createTransaction('BUY', 1.0, 50000, '2024-01-01');
        $this->createTransaction('BUY', 0.5, 55000, '2024-02-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(0, $pnl);
    }

    /**
     * Senaryo 2: 1 BUY + 1 SELL — basit kar.
     * BUY: 1 BTC @ 50,000
     * SELL: 1 BTC @ 65,000
     * Beklenen: PnL = (65000 - 50000) * 1 = +15,000
     */
    public function test_simple_buy_sell_profit(): void
    {
        $this->createTransaction('BUY', 1.0, 50000, '2024-01-01');
        $this->createTransaction('SELL', 1.0, 65000, '2024-03-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(15000, $pnl);
    }

    /**
     * Senaryo 3: 1 BUY + 1 SELL — zarar.
     * BUY: 1 BTC @ 60,000
     * SELL: 1 BTC @ 45,000
     * Beklenen: PnL = (45000 - 60000) * 1 = -15,000
     */
    public function test_simple_buy_sell_loss(): void
    {
        $this->createTransaction('BUY', 1.0, 60000, '2024-01-01');
        $this->createTransaction('SELL', 1.0, 45000, '2024-03-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(-15000, $pnl);
    }

    /**
     * Senaryo 4: Kısmi satım — FIFO ilk lot'u kısmen tüketir.
     * BUY: 2 BTC @ 50,000
     * BUY: 1 BTC @ 60,000
     * SELL: 1.5 BTC @ 70,000
     *
     * FIFO sırası:
     * - İlk 2 BTC lot'undan 1.5 BTC satılır
     * - PnL = (70000 - 50000) * 1.5 = +30,000
     */
    public function test_partial_sell_fifo_order(): void
    {
        $this->createTransaction('BUY', 2.0, 50000, '2024-01-01');
        $this->createTransaction('BUY', 1.0, 60000, '2024-02-01');
        $this->createTransaction('SELL', 1.5, 70000, '2024-03-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(30000, $pnl);
    }

    /**
     * Senaryo 5: Çoklu BUY farklı fiyatlarla + FIFO tüketim.
     * BUY: 1 BTC @ 40,000 (lot 1)
     * BUY: 1 BTC @ 50,000 (lot 2)
     * BUY: 1 BTC @ 60,000 (lot 3)
     * SELL: 2 BTC @ 55,000
     *
     * FIFO: Lot 1 tamamen satılır, Lot 2 tamamen satılır
     * PnL = (55000-40000)*1 + (55000-50000)*1 = 15000 + 5000 = +20,000
     */
    public function test_multiple_buys_fifo_consumption(): void
    {
        $this->createTransaction('BUY', 1.0, 40000, '2024-01-01');
        $this->createTransaction('BUY', 1.0, 50000, '2024-02-01');
        $this->createTransaction('BUY', 1.0, 60000, '2024-03-01');
        $this->createTransaction('SELL', 2.0, 55000, '2024-04-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(20000, $pnl);
    }

    /**
     * Senaryo 6: Void edilmiş kayıtlar hesaba dahil edilmemeli.
     * BUY: 1 BTC @ 50,000
     * BUY: 1 BTC @ 45,000 (VOID!)
     * SELL: 1 BTC @ 60,000
     *
     * Void olan alış yok sayılır. FIFO: 50K lot satılır.
     * PnL = (60000 - 50000) * 1 = +10,000
     */
    public function test_voided_transactions_excluded(): void
    {
        $this->createTransaction('BUY', 1.0, 50000, '2024-01-01');
        $this->createTransaction('BUY', 1.0, 45000, '2024-02-01', true);  // VOID
        $this->createTransaction('SELL', 1.0, 60000, '2024-03-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(10000, $pnl);
    }

    /**
     * Senaryo 7: Tüm pozisyonu kapat.
     * BUY: 2 BTC @ 50,000
     * SELL: 2 BTC @ 55,000
     *
     * PnL = (55000 - 50000) * 2 = +10,000
     * Net pozisyon = 0
     */
    public function test_full_position_close(): void
    {
        $this->createTransaction('BUY', 2.0, 50000, '2024-01-01');
        $this->createTransaction('SELL', 2.0, 55000, '2024-06-01');

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(10000, $pnl);
    }

    /**
     * Senaryo 8: Komisyon düşülmeli.
     * BUY: 1 BTC @ 50,000
     * SELL: 1 BTC @ 60,000, komisyon = 100
     *
     * PnL = (60000 - 50000) * 1 - 100 = +9,900
     */
    public function test_commission_deducted(): void
    {
        $this->createTransaction('BUY', 1.0, 50000, '2024-01-01');

        InvestmentTransaction::create([
            'workspace_id'       => $this->user->current_workspace_id,
            'created_by_user_id' => $this->user->id,
            'asset_id'           => $this->asset->id,
            'side'               => 'SELL',
            'quantity'           => 1.0,
            'unit_price'         => 60000,
            'total_amount'       => 60000,
            'commission'         => 100,
            'fx_rate_to_base'    => 1.0,
            'transaction_date'   => '2024-03-01',
        ]);

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(9900, $pnl);
    }

    /**
     * Senaryo 9: FX rate etkisi.
     * BUY: 1 BTC @ 50,000 (fx_rate = 30 → base cost = 1,500,000 TRY)
     * SELL: 1 BTC @ 55,000 (fx_rate = 32 → base proceeds = 1,760,000 TRY)
     *
     * PnL = (55000*32 - 50000*30) * 1 = 1,760,000 - 1,500,000 = +260,000 TRY
     */
    public function test_fx_rate_effect(): void
    {
        InvestmentTransaction::create([
            'workspace_id'       => $this->user->current_workspace_id,
            'created_by_user_id' => $this->user->id,
            'asset_id'           => $this->asset->id,
            'side'               => 'BUY',
            'quantity'           => 1.0,
            'unit_price'         => 50000,
            'total_amount'       => 50000,
            'commission'         => 0,
            'fx_rate_to_base'    => 30.0,
            'transaction_date'   => '2024-01-01',
        ]);

        InvestmentTransaction::create([
            'workspace_id'       => $this->user->current_workspace_id,
            'created_by_user_id' => $this->user->id,
            'asset_id'           => $this->asset->id,
            'side'               => 'SELL',
            'quantity'           => 1.0,
            'unit_price'         => 55000,
            'total_amount'       => 55000,
            'commission'         => 0,
            'fx_rate_to_base'    => 32.0,
            'transaction_date'   => '2024-03-01',
        ]);

        $pnl = $this->portfolioService->getFifoPnL($this->user, $this->asset->id);

        $this->assertEquals(260000, $pnl);
    }

    /**
     * Yardımcı: Transaction oluştur.
     */
    private function createTransaction(
        string $side,
        float $quantity,
        float $unitPrice,
        string $date,
        bool $isVoid = false
    ): InvestmentTransaction {
        return InvestmentTransaction::create([
            'workspace_id'       => $this->user->current_workspace_id,
            'created_by_user_id' => $this->user->id,
            'asset_id'           => $this->asset->id,
            'side'               => $side,
            'quantity'           => $quantity,
            'unit_price'         => $unitPrice,
            'total_amount'       => $quantity * $unitPrice,
            'commission'         => 0,
            'fx_rate_to_base'    => 1.0,
            'transaction_date'   => $date,
            'is_void'            => $isVoid,
            'void_reason'        => $isVoid ? 'Test void' : null,
            'voided_at'          => $isVoid ? now() : null,
        ]);
    }
}

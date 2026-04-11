<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ARCHITECTURE.md §2 — POSITION SUMMARY VIEW (computed, never stored)
     * v_positions: investment_transactions'dan anlık hesaplanan pozisyon özeti
     *
     * Bu VIEW hiç veri saklamaz, her sorgulandığında canlı hesaplar:
     * - net_quantity: BUY toplamı - SELL toplamı (void olmayan)
     * - total_cost_base: Toplam alım maliyeti (fx_rate ile base currency'ye çevrilmiş)
     * - total_sell_proceeds_base: Toplam satış geliri
     * - total_commission_base: Toplam komisyon
     * - first_trade, last_trade, trade_count
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW v_positions AS
            SELECT
                t.workspace_id,
                t.asset_id,
                a.asset_class,
                a.symbol,
                a.name,
                SUM(CASE WHEN side = 'BUY'  AND NOT is_void THEN quantity ELSE 0 END)
              - SUM(CASE WHEN side = 'SELL' AND NOT is_void THEN quantity ELSE 0 END)
                    AS net_quantity,
                SUM(CASE WHEN side = 'BUY' AND NOT is_void
                         THEN (total_amount + commission) * fx_rate_to_base ELSE 0 END)
                    AS total_cost_base,
                SUM(CASE WHEN side = 'SELL' AND NOT is_void
                         THEN (total_amount - commission) * fx_rate_to_base ELSE 0 END)
                    AS total_sell_proceeds_base,
                SUM(CASE WHEN NOT is_void THEN commission * fx_rate_to_base ELSE 0 END)
                    AS total_commission_base,
                MIN(t.transaction_date) AS first_trade,
                MAX(t.transaction_date) AS last_trade,
                COUNT(*) FILTER (WHERE NOT is_void) AS trade_count
            FROM investment_transactions t
            JOIN assets a ON a.id = t.asset_id
            GROUP BY t.workspace_id, t.asset_id, a.asset_class, a.symbol, a.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_positions");
    }
};

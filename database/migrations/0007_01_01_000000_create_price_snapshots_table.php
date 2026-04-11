<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ARCHITECTURE.md §2 — PRICE CACHE
     * price_snapshots: Binance, Yahoo gibi kaynaklardan çekilen fiyat verisi
     */
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');

            $table->decimal('price', 20, 8);
            $table->char('currency', 3)->default('USD');
            $table->string('source', 50);              // 'binance', 'yahoo', 'alphavantage'
            $table->timestamp('fetched_at')->useCurrent();
        });

        // Composite index: Son fiyatı hızlıca bulmak için (asset_id + fetched_at DESC)
        DB::statement("CREATE INDEX idx_price_asset_time ON price_snapshots (asset_id, fetched_at DESC)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};

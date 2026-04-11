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
     * fx_rate_snapshots: Döviz kuru verileri (USD/TRY, EUR/TRY, EUR/USD vb.)
     */
    public function up(): void
    {
        Schema::create('fx_rate_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('base_currency', 3);       // 'USD'
            $table->char('quote_currency', 3);      // 'TRY'
            $table->decimal('rate', 20, 8);
            $table->string('source', 50);            // 'yahoo', 'alphavantage'
            $table->timestamp('fetched_at')->useCurrent();
        });

        // Composite index: Belirli para çifti için son kuru hızlıca bulmak
        DB::statement("CREATE INDEX idx_fx_pair_time ON fx_rate_snapshots (base_currency, quote_currency, fetched_at DESC)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fx_rate_snapshots');
    }
};

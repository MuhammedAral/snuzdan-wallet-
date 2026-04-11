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
     * ARCHITECTURE.md §2 — INVESTMENT LEDGER
     * Append-only: NEVER DELETE or UPDATE amount columns.
     * Every event = one INSERT. Void = is_void = true.
     */
    public function up(): void
    {
        // PostgreSQL ENUM tipi
        DB::statement("CREATE TYPE transaction_side AS ENUM ('BUY', 'SELL')");

        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Workspace & user references
            $table->uuid('workspace_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces');
            $table->uuid('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            // Asset reference
            $table->uuid('asset_id');
            $table->foreign('asset_id')->references('id')->on('assets');

            // Transaction side — BUY or SELL
            $table->string('side', 4); // Will be cast to PostgreSQL ENUM below

            // Financial fields — high precision for crypto
            $table->decimal('quantity', 20, 8);
            $table->decimal('unit_price', 20, 8);
            $table->decimal('total_amount', 20, 8);    // = quantity × unit_price
            $table->decimal('commission', 20, 8)->default(0);
            $table->decimal('fx_rate_to_base', 20, 8)->default(1.0);

            $table->text('note')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            // Append-only soft delete (void)
            $table->boolean('is_void')->default(false);
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
        });

        // Cast side kolonu PostgreSQL ENUM tipine
        DB::statement("ALTER TABLE investment_transactions ALTER COLUMN side TYPE transaction_side USING side::transaction_side");

        // CHECK constraints — veritabanı seviyesinde veri bütünlüğü
        DB::statement("ALTER TABLE investment_transactions ADD CONSTRAINT chk_total CHECK (ABS(total_amount - quantity * unit_price) < 0.01)");
        DB::statement("ALTER TABLE investment_transactions ADD CONSTRAINT chk_positive_qty CHECK (quantity > 0)");
        DB::statement("ALTER TABLE investment_transactions ADD CONSTRAINT chk_positive_price CHECK (unit_price > 0)");

        // Indexes — ARCHITECTURE.md'deki tanımlar
        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->index('workspace_id', 'idx_inv_tx_workspace');
            $table->index('asset_id', 'idx_inv_tx_asset');
            $table->index(['workspace_id', 'transaction_date'], 'idx_inv_tx_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
        DB::statement("DROP TYPE IF EXISTS transaction_side");
    }
};

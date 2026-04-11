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
     * ARCHITECTURE.md §2 — INVESTMENT MODULE
     * assets tablosu: Yatırım varlıklarını tanımlar (BTC, AAPL, EUR/USD vb.)
     */
    public function up(): void
    {
        // PostgreSQL ENUM tipi oluştur
        DB::statement("CREATE TYPE asset_class AS ENUM ('CRYPTO', 'STOCK', 'FX')");

        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // asset_class enum — raw column çünkü Laravel enum() MySQL'e özgü davranabilir
            $table->string('asset_class', 10); // Backed by PostgreSQL ENUM via ALTER below
            $table->string('symbol', 20);          // 'BTC', 'AAPL', 'EUR/USD'
            $table->string('name', 255);
            $table->char('base_currency', 3)->default('USD');
            $table->timestamp('created_at')->useCurrent();

            // UNIQUE(asset_class, symbol) — aynı varlık iki kez eklenemez
            $table->unique(['asset_class', 'symbol'], 'uq_assets_class_symbol');
        });

        // Index: asset_class'a göre hızlı filtreleme
        Schema::table('assets', function (Blueprint $table) {
            $table->index('asset_class', 'idx_assets_class');
        });

        // Kolonu PostgreSQL ENUM tipine cast et
        DB::statement("ALTER TABLE assets ALTER COLUMN asset_class TYPE asset_class USING asset_class::asset_class");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
        DB::statement("DROP TYPE IF EXISTS asset_class");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── PostgreSQL ENUM Types ──
        DB::statement("CREATE TYPE recurring_period AS ENUM ('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY')");

        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id');
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('account_id');
            $table->uuid('category_id');
            $table->decimal('amount', 20, 2);
            $table->char('currency', 3)->default('USD');
            $table->text('note')->nullable();
            $table->date('next_run_date');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
        });

        // Add enum columns via raw SQL
        DB::statement("ALTER TABLE recurring_transactions ADD COLUMN direction flow_direction NOT NULL");
        DB::statement("ALTER TABLE recurring_transactions ADD COLUMN period recurring_period NOT NULL");

        // Indexes
        DB::statement("CREATE INDEX idx_recurring_tx_run ON recurring_transactions (next_run_date) WHERE is_active = TRUE");
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
        DB::statement("DROP TYPE IF EXISTS recurring_period");
    }
};

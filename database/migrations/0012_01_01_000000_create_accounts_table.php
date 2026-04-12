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
        DB::statement("CREATE TYPE account_type AS ENUM ('CASH', 'BANK', 'CREDIT_CARD', 'E_WALLET')");

        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id');
            $table->uuid('created_by_user_id')->nullable();
            $table->string('name', 100);
            $table->char('currency', 3)->default('USD');
            $table->decimal('balance', 20, 2)->default(0);
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Add enum column via raw SQL
        DB::statement("ALTER TABLE accounts ADD COLUMN type account_type NOT NULL");

        // Indexes
        DB::statement("CREATE INDEX idx_accounts_workspace ON accounts (workspace_id)");
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
        DB::statement("DROP TYPE IF EXISTS account_type");
    }
};

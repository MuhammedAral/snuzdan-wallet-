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
        DB::statement("CREATE TYPE flow_direction AS ENUM ('INCOME', 'EXPENSE')");
        DB::statement("CREATE TYPE category_type AS ENUM ('SYSTEM', 'CUSTOM')");

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id')->nullable();
            $table->string('name', 100);
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            $table->foreign('workspace_id')->references('id')->on('workspaces');
        });

        // Add enum columns via raw SQL
        DB::statement("ALTER TABLE categories ADD COLUMN direction flow_direction NOT NULL");
        DB::statement("ALTER TABLE categories ADD COLUMN cat_type category_type NOT NULL DEFAULT 'CUSTOM'");

        // CHECK constraint: SYSTEM → workspace_id NULL, CUSTOM → workspace_id NOT NULL
        DB::statement("
            ALTER TABLE categories ADD CONSTRAINT chk_category_owner CHECK (
                (cat_type = 'SYSTEM' AND workspace_id IS NULL)
                OR
                (cat_type = 'CUSTOM' AND workspace_id IS NOT NULL)
            )
        ");

        // Indexes
        DB::statement("CREATE INDEX idx_cat_workspace ON categories (workspace_id)");
        DB::statement("CREATE INDEX idx_cat_direction ON categories (direction)");
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        DB::statement("DROP TYPE IF EXISTS category_type");
        DB::statement("DROP TYPE IF EXISTS flow_direction");
    }
};

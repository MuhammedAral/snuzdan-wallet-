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
        DB::statement("CREATE TYPE user_status AS ENUM ('pending', 'active', 'suspended')");
        DB::statement("CREATE TYPE workspace_role AS ENUM ('owner', 'editor', 'viewer')");

        // ── 1. USERS ──
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email')->unique();
            $table->string('password_hash')->nullable();
            $table->string('display_name', 100);
            $table->char('base_currency', 3)->default('USD');
            $table->string('theme', 10)->default('dark');
            $table->boolean('email_verified')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestampTz('two_factor_confirmed_at')->nullable();
            $table->uuid('current_workspace_id')->nullable();
            $table->text('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
        });
        // Add the enum column via raw SQL (Laravel Schema doesn't support custom PG enums)
        DB::statement("ALTER TABLE users ADD COLUMN status user_status NOT NULL DEFAULT 'pending'");

        // ── 2. WORKSPACES ──
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 100);
            $table->uuid('created_by');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            $table->foreign('created_by')->references('id')->on('users');
        });

        // ── 3. Circular FK: users.current_workspace_id → workspaces ──
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_workspace_id', 'fk_current_workspace')
                  ->references('id')->on('workspaces')->onDelete('set null');
        });

        // ── 4. WORKSPACE_USER pivot ──
        Schema::create('workspace_user', function (Blueprint $table) {
            $table->uuid('workspace_id');
            $table->uuid('user_id');
            $table->timestampTz('joined_at')->default(DB::raw('NOW()'));
            $table->primary(['workspace_id', 'user_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        DB::statement("ALTER TABLE workspace_user ADD COLUMN role workspace_role NOT NULL DEFAULT 'viewer'");

        // ── 5. OAUTH ACCOUNTS ──
        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id')->nullable();
            $table->uuid('user_id');
            $table->string('provider', 50);
            $table->string('provider_id');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->unique(['provider', 'provider_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ── 6. VERIFICATION TOKENS ──
        Schema::create('verification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id')->nullable();
            $table->uuid('user_id');
            $table->string('token')->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->foreign('workspace_id')->references('id')->on('workspaces');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ── 7. LARAVEL FRAMEWORK TABLES ──
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('verification_tokens');
        Schema::dropIfExists('oauth_accounts');
        Schema::dropIfExists('workspace_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('fk_current_workspace');
        });
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('users');
        DB::statement("DROP TYPE IF EXISTS workspace_role");
        DB::statement("DROP TYPE IF EXISTS user_status");
    }
};

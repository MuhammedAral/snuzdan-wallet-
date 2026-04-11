<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Workspaces (Basit hali, A-4 için Foreign Key hatalarını önlemek amacıyla)
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->uuid('created_by'); // Reference users after creation, so just uuid for now
            $table->timestampsPaginated = false;
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        // 2. Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255)->nullable();
            $table->string('display_name', 100);
            $table->char('base_currency', 3)->default('USD');
            $table->string('theme', 10)->default('dark');
            // user_status ENUM ('pending', 'active', 'suspended')
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('email_verified')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            $table->uuid('current_workspace_id')->nullable();
            $table->foreign('current_workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            
            $table->text('avatar_url')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
        
        // Add foreign key to workspaces manually now that users exists
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
        
        // workspace_user pivot
        Schema::create('workspace_user', function (Blueprint $table) {
            $table->uuid('workspace_id');
            $table->uuid('user_id');
            // role ENUM
            $table->enum('role', ['owner', 'editor', 'viewer'])->default('viewer');
            $table->timestamp('joined_at')->useCurrent();
            
            $table->primary(['workspace_id', 'user_id']);
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->nullable(); // Set nullable initially if required or just keep
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->uuid('created_by_user_id');
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            
            $table->string('provider', 50);
            $table->string('provider_id', 255);
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['provider', 'provider_id']);
        });

        Schema::create('verification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->nullable();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->uuid('created_by_user_id');
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_tokens');
        Schema::dropIfExists('oauth_accounts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('workspace_user');
        
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
        });
        
        Schema::dropIfExists('users');
        Schema::dropIfExists('workspaces');
    }
};

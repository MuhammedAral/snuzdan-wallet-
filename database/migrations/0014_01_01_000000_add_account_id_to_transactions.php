<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('income_transactions', 'account_id')) {
            Schema::table('income_transactions', function (Blueprint $table) {
                $table->uuid('account_id')->nullable();
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }

        if (!Schema::hasColumn('expense_transactions', 'account_id')) {
            Schema::table('expense_transactions', function (Blueprint $table) {
                $table->uuid('account_id')->nullable();
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });

        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};

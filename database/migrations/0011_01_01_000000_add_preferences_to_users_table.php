<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'base_currency')) {
                $table->string('base_currency', 3)->default('TRY')->after('current_workspace_id');
            }
            if (!Schema::hasColumn('users', 'theme')) {
                $table->string('theme', 20)->default('dark')->after('base_currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'theme']);
        });
    }
};

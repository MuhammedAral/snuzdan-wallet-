<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('workspace_id');
            $table->uuid('created_by_user_id');
            $table->uuid('category_id');
            // $table->uuid('account_id'); // Gelecek aşamaya hazırlık (A-5 -> Bank/Cash accounts)
            
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->timestampTz('expense_date');
            
            $table->text('notes')->nullable();
            $table->boolean('is_void')->default(false); // Append-only İptal Mekanizması
            
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            // Yabancı Anahtarlar (Foreign Keys)
            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        });

        // Tutar kontrolü (Sadece pozitif değer girilmeli)
        DB::statement("ALTER TABLE expense_transactions ADD CONSTRAINT chk_exp_amount_positive CHECK (amount > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id');
            
            $table->text('prompt');
            $table->jsonb('response');
            
            $table->string('action_type', 50)->default('PARSE_TRANSACTION'); // Örn: PARSE_TRANSACTION, SUGGEST_CATEGORY, MONTHLY_REPORT
            $table->boolean('was_accepted')->nullable(); // Kullanıcı öneriyi kabul etti mi reddetti mi?
            
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            // Yabancı Anahtarlar
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
    }
};

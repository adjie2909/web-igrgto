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
        Schema::create('request_headers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('tanggal_request');

            $table->tinyInteger('status')->default(0);
            // 0 pending
            // 1 approved
            // 2 diproses
            // 3 selesai

            $table->integer('current_approval_level')->default(1);

            // 🔥 TAMBAHAN APPROVAL
            $table->foreignId('approved_by_level1')->nullable()->constrained('users');
            $table->foreignId('approved_by_level2')->nullable()->constrained('users');
            $table->foreignId('approved_by_level3')->nullable()->constrained('users');

            $table->timestamp('approved_at_level1')->nullable();
            $table->timestamp('approved_at_level2')->nullable();
            $table->timestamp('approved_at_level3')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_headers');
    }
};

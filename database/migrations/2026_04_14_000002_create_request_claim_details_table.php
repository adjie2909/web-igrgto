<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_claim_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('request_claims')->cascadeOnDelete();
            $table->foreignId('request_detail_id')->constrained('request_details')->cascadeOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
            $table->integer('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_claim_details');
    }
};

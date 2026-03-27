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
        Schema::create('request_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_id')
                ->constrained('request_headers')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->nullable()
                ->constrained('barangs')
                ->nullOnDelete();

            $table->integer('qty');

            $table->string('keterangan')->nullable();
            // dipakai kalau barang tidak ada di master

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requestdetails');
    }
};

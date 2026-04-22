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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('judul');
            $table->text('deskripsi');
            $table->string('current_handler')->default('EDP');
            // EDP / PGA

            $table->tinyInteger('level')->default(1);
            // 1 = EDP
            // 2 = PGA

            $table->tinyInteger('status')->default(0); 
            // 0=open, 1=on progress, 2=closed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

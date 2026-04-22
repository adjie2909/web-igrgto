<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            if (!Schema::hasColumn('request_details', 'qty_original')) {
                $table->integer('qty_original')->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            if (Schema::hasColumn('request_details', 'qty_original')) {
                $table->dropColumn('qty_original');
            }
        });
    }
};


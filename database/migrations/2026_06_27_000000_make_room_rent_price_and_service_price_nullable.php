<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'rent_price')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->integer('rent_price')->nullable()->change();
            });
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'price')) {
            Schema::table('services', function (Blueprint $table) {
                $table->integer('price')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'rent_price')) {
            DB::table('rooms')->whereNull('rent_price')->update(['rent_price' => 0]);

            Schema::table('rooms', function (Blueprint $table) {
                $table->integer('rent_price')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'price')) {
            DB::table('services')->whereNull('price')->update(['price' => 0]);

            Schema::table('services', function (Blueprint $table) {
                $table->integer('price')->nullable(false)->change();
            });
        }
    }
};

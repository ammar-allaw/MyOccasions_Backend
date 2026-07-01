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
        if (! Schema::hasTable('service_providers')) {
            return;
        }

        if (! Schema::hasColumn('service_providers', 'landline_phone')) {
            Schema::table('service_providers', function (Blueprint $table) {
                $table->string('landline_phone')->nullable()->after('address_url');
            });
        }

        if (! Schema::hasColumn('service_providers', 'use_landline_for_calls')) {
            Schema::table('service_providers', function (Blueprint $table) {
                $table->boolean('use_landline_for_calls')->default(false)->after('landline_phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('service_providers')) {
            return;
        }

        Schema::table('service_providers', function (Blueprint $table) {
            if (Schema::hasColumn('service_providers', 'use_landline_for_calls')) {
                $table->dropColumn('use_landline_for_calls');
            }

            if (Schema::hasColumn('service_providers', 'landline_phone')) {
                $table->dropColumn('landline_phone');
            }
        });
    }
};

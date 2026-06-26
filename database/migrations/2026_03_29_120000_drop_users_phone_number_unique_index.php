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
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users')
            ->where('index_name', 'users_phone_number_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_phone_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users')
            ->where('index_name', 'users_phone_number_unique')
            ->exists();

        $hasDuplicatePhoneNumbers = DB::table('users')
            ->select('phone_number')
            ->whereNotNull('phone_number')
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (!$indexExists && !$hasDuplicatePhoneNumbers) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone_number');
            });
        }
    }
};

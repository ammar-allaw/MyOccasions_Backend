<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('last_sent_at');
            $table->char('reset_token_hash', 64)->nullable()->unique()->after('verified_at');
            $table->timestamp('reset_token_expires_at')->nullable()->after('reset_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropUnique(['reset_token_hash']);
            $table->dropColumn([
                'verified_at',
                'reset_token_hash',
                'reset_token_expires_at',
            ]);
        });
    }
};

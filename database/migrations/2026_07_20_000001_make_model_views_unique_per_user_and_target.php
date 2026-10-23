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
        DB::statement("
            DELETE duplicate_views FROM model_views duplicate_views
            INNER JOIN model_views original_views
                ON duplicate_views.viewable_type = original_views.viewable_type
                AND duplicate_views.viewable_id = original_views.viewable_id
                AND duplicate_views.user_id = original_views.user_id
                AND duplicate_views.id > original_views.id
            WHERE duplicate_views.user_id IS NOT NULL
        ");

        Schema::table('model_views', function (Blueprint $table) {
            $table->dropUnique('model_views_user_daily_unique');
            $table->unique(['viewable_type', 'viewable_id', 'user_id'], 'model_views_user_target_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            $table->dropUnique('model_views_user_target_unique');
            $table->unique(['viewable_type', 'viewable_id', 'user_id', 'viewed_on'], 'model_views_user_daily_unique');
        });
    }
};

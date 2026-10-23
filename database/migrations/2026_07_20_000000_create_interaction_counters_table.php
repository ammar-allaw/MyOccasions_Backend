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
        Schema::create('interaction_counters', function (Blueprint $table) {
            $table->id();
            $table->morphs('interactable');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->timestamps();

            $table->unique(['interactable_type', 'interactable_id'], 'interaction_counters_target_unique');
        });

        DB::table('model_views')
            ->select('viewable_type', 'viewable_id', DB::raw('COUNT(DISTINCT user_id) as views_count'))
            ->groupBy('viewable_type', 'viewable_id')
            ->orderBy('viewable_type')
            ->chunk(200, function ($viewCounts) {
                foreach ($viewCounts as $viewCount) {
                    DB::table('interaction_counters')->updateOrInsert(
                        [
                            'interactable_type' => $viewCount->viewable_type,
                            'interactable_id' => $viewCount->viewable_id,
                        ],
                        [
                            'views_count' => $viewCount->views_count,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            });

        DB::table('model_likes')
            ->select('likeable_type', 'likeable_id', DB::raw('COUNT(*) as likes_count'))
            ->groupBy('likeable_type', 'likeable_id')
            ->orderBy('likeable_type')
            ->chunk(200, function ($likeCounts) {
                foreach ($likeCounts as $likeCount) {
                    DB::table('interaction_counters')->updateOrInsert(
                        [
                            'interactable_type' => $likeCount->likeable_type,
                            'interactable_id' => $likeCount->likeable_id,
                        ],
                        [
                            'likes_count' => $likeCount->likes_count,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaction_counters');
    }
};

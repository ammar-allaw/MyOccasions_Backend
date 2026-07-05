<?php

use App\Models\User;
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
        Schema::create('model_likes', function (Blueprint $table) {
            $table->id();
            $table->morphs('likeable');
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['likeable_type', 'likeable_id', 'user_id'], 'model_likes_user_unique');
            $table->index(['likeable_type', 'likeable_id'], 'model_likes_target_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_likes');
    }
};

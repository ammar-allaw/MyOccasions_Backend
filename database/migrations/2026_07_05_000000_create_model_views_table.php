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
        Schema::create('model_views', function (Blueprint $table) {
            $table->id();
            $table->morphs('viewable');
            $table->foreignIdFor(User::class)->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->date('viewed_on');
            $table->timestamps();

            $table->unique(['viewable_type', 'viewable_id', 'user_id', 'viewed_on'], 'model_views_user_daily_unique');
            $table->index(['viewable_type', 'viewable_id', 'viewed_on'], 'model_views_target_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_views');
    }
};

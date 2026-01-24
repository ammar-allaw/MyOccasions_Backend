<?php

use App\Models\OrderStatus;
use App\Models\ServiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('notes', function (Blueprint $table) {
    //         $table->id();
    //         $table->text('message');
    //         $table->foreignIdFor(OrderStatus::class)->constrained()
    //         ->cascadeOnDelete()->cascadeOnUpdate();
    //         $table->timestamps();
    //     });
    // }

    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     Schema::dropIfExists('notes');
    // }
};

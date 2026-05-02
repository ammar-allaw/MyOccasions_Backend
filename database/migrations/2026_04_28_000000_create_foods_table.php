<?php

use App\Models\MainKey;
use App\Models\ServiceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en');
            $table->foreignIdFor(MainKey::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(ServiceProvider::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->text('short_description')->nullable();
            $table->text('short_description_en')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('preparation_time')->nullable();
            $table->integer('calories')->nullable();
            $table->text('ingredients')->nullable();
            $table->string('portion_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};

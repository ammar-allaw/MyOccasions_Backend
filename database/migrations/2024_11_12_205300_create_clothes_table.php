<?php

use App\Models\Subtype;
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
        Schema::create('clothes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description');
            $table->text('description_en')->nullable();
            $table->foreignIdFor(Subtype::class)->constrained()
            ->cascadeOnDelete()->cascadeOnUpdate();          
            $table->unsignedBigInteger('selling_price');
            $table->unsignedBigInteger('rent_price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clothes');
    }
};

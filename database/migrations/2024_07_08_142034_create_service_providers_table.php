<?php

use App\Models\Government;
use App\Models\Region;
use App\Models\ServiceProvider;
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
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en');
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('location');
            $table->string('location_en');
            $table->string('address_url')->nullable();
            $table->foreignIdFor(Government::class,'government_id')->constrained();
            $table->foreignIdFor(Region::class,'region_id')->constrained();
            // $table->foreignIdFor(ServiceProvider::class,'service_provider_id')->constrained()
            // ->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};

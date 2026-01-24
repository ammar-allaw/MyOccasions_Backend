<?php

use App\Models\ServiceProvider;
use App\Models\Type;
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
        Schema::create('service_provider_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ServiceProvider::class)->constrained()
            ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Type::class)->constrained()
            ->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_types');
    }
};

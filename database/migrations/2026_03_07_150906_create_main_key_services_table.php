<?php

use App\Models\MainKey;
use App\Models\Service;
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
        Schema::create('main_key_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MainKey::class)->constrained()
            ->onDelete('cascade')->onUpdate('cascade');
            $table->foreignIdFor(Service::class)->constrained()
            ->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_key_services');
    }
};

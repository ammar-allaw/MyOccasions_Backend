<?php

use App\Models\Role;
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
        Schema::create('main_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('key_en');
            $table->foreignIdFor(Role::class)->constrained()->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unique(['key', 'role_id']);
            $table->unique(['key_en', 'role_id']);
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_keys');
    }
};

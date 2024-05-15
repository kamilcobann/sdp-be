<?php

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
        Schema::create('kanban_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Kanban::class,'kanban_id')->constrained('kanbans')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\User::class,'user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_user');
    }
};

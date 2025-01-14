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
        Schema::create('kanban_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignIdFor(\App\Models\Kanban::class,'by_kanban_id')->constrained('kanbans');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_lists');
    }
};

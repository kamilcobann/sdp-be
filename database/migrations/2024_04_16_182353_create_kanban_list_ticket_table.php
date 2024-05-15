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
        Schema::create('kanban_list_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Ticket::class,'ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignIdFor(\App\Models\KanbanList::class,'kanban_list_id')->constrained('kanban_lists')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_list_ticket');
    }
};

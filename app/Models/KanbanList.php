<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanList extends Model
{
    use HasFactory;

    protected $fillable = [
        "title"
    ];

    public function belongingKanban():BelongsTo{
        return $this->belongsTo(Kanban::class,'by_kanban_id');
    }

    /**
     * Get all of the tickets for the KanbanList
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class,'by_kanban_list_id');
    }
}

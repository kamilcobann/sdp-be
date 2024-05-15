<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        "by_kanban_list_id"
    ];

    /**
     * Get the user that owns the Ticket
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attachedKanbanList(): BelongsTo
    {
        return $this->belongsTo(KanbanList::class, 'by_kanban_list_id');
    }

    public function assignedUsers(): BelongsToMany
    {
        // return $this->hasMany(User::class, 'by_user_id');
        return $this->belongsToMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class,'by_user_id');
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class,'by_ticket_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kanban extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'by_user_id',
        'by_project_id'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class,'by_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function relatedProject():BelongsTo{
        return $this->belongsTo(Project::class, "by_project_id");
    }

    public function kanbanLists(): HasMany{
        return $this->hasMany(KanbanList::class,'by_kanban_id');
    }

}

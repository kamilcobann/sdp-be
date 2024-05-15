<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'is_active'
    ];

    public function owner():BelongsTo{
        return $this->belongsTo(User::class, "by_user_id");
    }

    /**
     * The members that belong to the Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function kanbans():HasMany{
        return $this->hasMany(Kanban::class,'by_project_id');
    }

}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-configured meal voting option.
 *
 * Members can only vote for options an administrator has explicitly created in
 * Settings -> Meal Voting, so the choice list is never hard-coded. Mirrors the
 * SubsidySource pattern: rows belong to an institution (or are shared when
 * institution_id is null).
 */
class MealVoteOption extends Model
{
    use BelongsToInstitution;
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'label',
        'description',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function votes()
    {
        return $this->hasMany(MealVote::class, 'option_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

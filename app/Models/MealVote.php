<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A member's vote for one admin-configured meal option, per week. */
class MealVote extends Model
{
    use BelongsToInstitution;
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'option_id',
        'week_start',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function option()
    {
        return $this->belongsTo(MealVoteOption::class, 'option_id');
    }
}

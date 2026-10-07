<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * A member's meal scheduling / off-on rule.
 *
 * `taking_meals === false` means the member is NOTIFYING the manager they will
 * SKIP the selected meals for the window; `true` means they WILL take them (e.g.
 * re-confirming after a trip). Recurrence lets "no breakfast on Sundays" be set
 * once instead of week by week.
 */
class MealSchedule extends Model
{
    use BelongsToInstitution;
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'start_date',
        'end_date',
        'recurrence',
        'interval_days',
        'taking_meals',
        'breakfast',
        'lunch',
        'dinner',
        'note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'interval_days' => 'integer',
        'taking_meals' => 'boolean',
        'breakfast' => 'boolean',
        'lunch' => 'boolean',
        'dinner' => 'boolean',
    ];

    /**
     * A pre-existing (other-branch) `meal_schedules` may still enforce a NOT NULL
     * `starts_on` column. Mirror our `start_date` into it on save so an insert
     * never violates that constraint - we neither drop nor alter the old column.
     */
    protected static function booted(): void
    {
        static::saving(function (MealSchedule $schedule) {
            if ($schedule->start_date && Schema::hasColumn('meal_schedules', 'starts_on')) {
                $schedule->setAttribute('starts_on', $schedule->start_date);
            }
        });
    }

    public const RECURRENCES = [
        'one_time' => 'One time',
        'daily' => 'Every day',
        'weekly' => 'Every week',
        'interval' => 'At an interval',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Which meals this rule covers, as a list of labels. */
    public function mealLabels(): array
    {
        $labels = [];
        if ($this->breakfast) $labels[] = 'Breakfast';
        if ($this->lunch) $labels[] = 'Lunch';
        if ($this->dinner) $labels[] = 'Dinner';

        return $labels;
    }
}

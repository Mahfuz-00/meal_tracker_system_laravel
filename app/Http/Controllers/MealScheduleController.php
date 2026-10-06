<?php

namespace App\Http\Controllers;

use App\Models\MealSchedule;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * MEMBER MEAL SCHEDULING ("off / on" notifications).
 *
 * Members tell the meal manager whether they WILL or WON'T take meals on a date
 * or range (one-time, daily, weekly, or every N days). The manager's board reads
 * the same rows to know who to cook for on any given day.
 */
class MealScheduleController extends Controller
{
    /* ------------------------------ Member ------------------------------ */

    public function memberIndex(Request $request)
    {
        $student = $request->user()->studentRecord();

        $schedules = $student
            ? MealSchedule::query()
                ->where('student_id', $student->id)
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (MealSchedule $s) => $this->present($s))
            : collect();

        return Inertia::render('Member/Schedule', [
            'hasMemberRecord' => (bool) $student,
            'schedules' => $schedules,
            'recurrences' => collect(MealSchedule::RECURRENCES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $student = $request->user()->studentRecord();

        if (! $student) {
            return back()->with('error', 'Your account is not linked to a member record yet.');
        }

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'recurrence' => ['required', Rule::in(array_keys(MealSchedule::RECURRENCES))],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'taking_meals' => ['required', 'boolean'],
            'breakfast' => ['boolean'],
            'lunch' => ['boolean'],
            'dinner' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if (! ($data['breakfast'] ?? true) && ! ($data['lunch'] ?? true) && ! ($data['dinner'] ?? true)) {
            return back()->with('error', 'Select at least one meal.');
        }

        if ($data['recurrence'] === 'interval' && blank($data['interval_days'] ?? null)) {
            return back()->with('error', 'Enter how many days between each repeat.');
        }

        $schedule = MealSchedule::create([
            'institution_id' => $student->institution_id,
            'student_id' => $student->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? $data['start_date'],
            'recurrence' => $data['recurrence'],
            'interval_days' => $data['interval_days'] ?? null,
            'taking_meals' => $data['taking_meals'],
            'breakfast' => $data['breakfast'] ?? true,
            'lunch' => $data['lunch'] ?? true,
            'dinner' => $data['dinner'] ?? true,
            'note' => $data['note'] ?? null,
            'status' => 'active',
            'created_by' => $request->user()->id,
        ]);

        // Tell the manager (and admins as a safety net) straight away.
        Notifier::mealScheduleSubmitted($schedule, $request->user());

        return back()->with('success', 'Your meal schedule was sent to the manager.');
    }

    public function destroy(Request $request, MealSchedule $mealSchedule)
    {
        $student = $request->user()->studentRecord();

        if (! $student || $mealSchedule->student_id !== $student->id) {
            return back()->with('error', 'Schedule not found.');
        }

        // Keep the row for the manager's audit trail; mark it cancelled.
        $mealSchedule->update(['status' => 'cancelled']);

        return back()->with('success', 'Schedule cancelled.');
    }

    /* ----------------------------- Manager ------------------------------ */

    public function managerIndex(Request $request)
    {
        // null = unrestricted within the institution; array = only these students.
        $scoped = $request->user()->scopedStudentIds();
        $date = (string) $request->query('date', now()->toDateString());

        $schedules = MealSchedule::query()
            ->active()
            ->with('student:id,name,roll')
            ->when($scoped !== null, fn ($q) => $q->whereIn('student_id', $scoped))
            ->orderByDesc('start_date')
            ->get();

        // Who is off / on for the selected day (recurrence applied).
        $onDate = $schedules
            ->filter(fn (MealSchedule $s) => $this->appliesOn($s, $date))
            ->map(fn (MealSchedule $s) => [
                'student' => $s->student?->name,
                'roll' => $s->student?->roll,
                'meals' => $s->mealLabels(),
                'taking_meals' => $s->taking_meals,
                'note' => $s->note,
            ])
            ->values();

        return Inertia::render('Meals/Schedules/Index', [
            'schedules' => $schedules->map(fn (MealSchedule $s) => $this->present($s)),
            'date' => $date,
            'onDate' => $onDate,
        ]);
    }

    /* ----------------------------- Helpers ------------------------------ */

    /** Does a schedule apply on the given date, honouring its recurrence? */
    protected function appliesOn(MealSchedule $schedule, string $date): bool
    {
        $day = Carbon::parse($date);
        $start = $schedule->start_date;

        if ($day->lt($start)) {
            return false;
        }

        // A recurring rule may be open-ended; a one-time rule ends on its day.
        if ($schedule->end_date && $day->gt($schedule->end_date)) {
            return false;
        }

        return match ($schedule->recurrence) {
            'one_time' => $day->isSameDay($start),
            'daily' => true,
            'weekly' => $day->dayOfWeek === $start->dayOfWeek,
            'interval' => $start->diffInDays($day) % max(1, (int) $schedule->interval_days) === 0,
            default => false,
        };
    }

    protected function present(MealSchedule $s): array
    {
        return [
            'id' => $s->id,
            'student' => $s->student?->name,
            'roll' => $s->student?->roll,
            'start_date' => $s->start_date?->format('j M Y'),
            'end_date' => $s->end_date?->format('j M Y'),
            'recurrence' => $s->recurrence,
            'recurrence_label' => MealSchedule::RECURRENCES[$s->recurrence] ?? $s->recurrence,
            'interval_days' => $s->interval_days,
            'taking_meals' => $s->taking_meals,
            'meals' => $s->mealLabels(),
            'note' => $s->note,
            'status' => $s->status,
        ];
    }
}

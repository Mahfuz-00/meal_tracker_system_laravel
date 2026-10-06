<?php

namespace App\Http\Controllers;

use App\Models\MealSuggestion;
use App\Models\MealVote;
use App\Models\MealVoteOption;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * MEAL VOTING + MEMBER SUGGESTIONS.
 *
 * Members vote (one vote per week, changeable) for an option an ADMIN configured
 * in Settings, and submit free-text meal suggestions. Managers see the live
 * tallies and every suggestion on their board.
 */
class MealVotingController extends Controller
{
    /** The Monday that starts the current voting week. */
    protected function weekStart(): string
    {
        return now()->startOfWeek()->toDateString();
    }

    /* ------------------------------ Member ------------------------------ */

    public function memberIndex(Request $request)
    {
        $student = $request->user()->studentRecord();
        $week = $this->weekStart();

        $tallies = MealVote::query()
            ->whereDate('week_start', $week)
            ->selectRaw('option_id, count(*) as total')
            ->groupBy('option_id')
            ->pluck('total', 'option_id');

        $options = MealVoteOption::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(fn (MealVoteOption $o) => [
                'id' => $o->id,
                'label' => $o->label,
                'description' => $o->description,
                'votes' => (int) ($tallies[$o->id] ?? 0),
            ]);

        $myVote = $student
            ? MealVote::query()->where('student_id', $student->id)->whereDate('week_start', $week)->value('option_id')
            : null;

        $suggestions = $student
            ? MealSuggestion::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn (MealSuggestion $s) => $this->presentSuggestion($s))
            : collect();

        return Inertia::render('Member/Voting', [
            'hasMemberRecord' => (bool) $student,
            'options' => $options,
            'myVote' => $myVote,
            'weekStart' => $week,
            'suggestions' => $suggestions,
        ]);
    }

    public function vote(Request $request)
    {
        $student = $request->user()->studentRecord();

        if (! $student) {
            return back()->with('error', 'Your account is not linked to a member record yet.');
        }

        $data = $request->validate([
            'option_id' => ['required', 'integer', 'exists:meal_vote_options,id'],
        ]);

        // One vote per member per week; voting again replaces the previous pick.
        MealVote::updateOrCreate(
            ['student_id' => $student->id, 'week_start' => $this->weekStart()],
            ['institution_id' => $student->institution_id, 'option_id' => $data['option_id']],
        );

        return back()->with('success', 'Your vote has been counted.');
    }

    public function suggest(Request $request)
    {
        $student = $request->user()->studentRecord();

        if (! $student) {
            return back()->with('error', 'Your account is not linked to a member record yet.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $suggestion = MealSuggestion::create([
            'institution_id' => $student->institution_id,
            'student_id' => $student->id,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'status' => 'pending',
        ]);

        Notifier::mealSuggestionSubmitted($suggestion, $request->user());

        return back()->with('success', 'Suggestion sent to the manager.');
    }

    /* ----------------------------- Manager ------------------------------ */

    public function managerIndex(Request $request)
    {
        $week = (string) $request->query('week', $this->weekStart());
        $scoped = $request->user()->scopedStudentIds();

        $tallies = MealVote::query()
            ->whereDate('week_start', $week)
            ->selectRaw('option_id, count(*) as total')
            ->groupBy('option_id')
            ->pluck('total', 'option_id');

        $options = MealVoteOption::query()
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(fn (MealVoteOption $o) => [
                'id' => $o->id,
                'label' => $o->label,
                'is_active' => (bool) $o->is_active,
                'votes' => (int) ($tallies[$o->id] ?? 0),
            ]);

        $suggestions = MealSuggestion::query()
            ->with('student:id,name,roll')
            ->when($scoped !== null, fn ($q) => $q->whereIn('student_id', $scoped))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (MealSuggestion $s) => $this->presentSuggestion($s, true));

        return Inertia::render('Meals/Voting/Index', [
            'week' => $week,
            'options' => $options,
            'suggestions' => $suggestions,
        ]);
    }

    protected function presentSuggestion(MealSuggestion $s, bool $withStudent = false): array
    {
        return array_merge([
            'id' => $s->id,
            'title' => $s->title,
            'body' => $s->body,
            'status' => $s->status,
            'created_at' => $s->created_at?->format('j M Y, H:i'),
        ], $withStudent ? [
            'student' => $s->student?->name,
            'roll' => $s->student?->roll,
        ] : []);
    }
}

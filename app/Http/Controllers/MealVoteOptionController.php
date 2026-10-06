<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MealVoteOption;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ADMIN management of the meal voting options.
 *
 * The choices members vote on are explicitly configured here (never hard-coded),
 * mirroring the SubsidySource pattern: rows belong to an institution (or are
 * shared defaults when institution_id is null).
 */
class MealVoteOptionController extends Controller
{
    public function index(Request $request)
    {
        $institution = Institution::current();

        $options = MealVoteOption::query()
            ->where(fn ($q) => $q->whereNull('institution_id')->orWhere('institution_id', $institution?->id))
            ->withCount(['votes as vote_count'])
            ->orderBy('sort')
            ->orderBy('label')
            ->get()
            ->map(fn (MealVoteOption $o) => [
                'id' => $o->id,
                'label' => $o->label,
                'description' => $o->description,
                'sort' => $o->sort,
                'is_active' => (bool) $o->is_active,
                'vote_count' => $o->vote_count,
            ]);

        return Inertia::render('Settings/MealVoting', [
            'options' => $options,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        MealVoteOption::create([
            'institution_id' => Institution::current()?->id,
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', "Voting option \"{$data['label']}\" added.");
    }

    public function update(Request $request, MealVoteOption $voteOption)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);

        $voteOption->update($data);

        return back()->with('success', "Voting option \"{$voteOption->label}\" updated.");
    }

    public function destroy(MealVoteOption $voteOption)
    {
        if ($voteOption->votes()->exists()) {
            return back()->with(
                'error',
                "Cannot delete \"{$voteOption->label}\" - it already has votes. Deactivate it instead."
            );
        }

        $label = $voteOption->label;
        $voteOption->delete();

        return back()->with('success', "Voting option \"{$label}\" removed.");
    }
}

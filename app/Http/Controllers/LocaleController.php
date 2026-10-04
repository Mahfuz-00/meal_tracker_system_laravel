<?php

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Settings -> Language screen.
 *
 * Language is a PERSONAL preference (like the theme), so it is stored on the
 * account, not the institution: two people in the same workspace can read the
 * platform in different languages. The choice lives in
 * `user_settings.settings.locale` - the generic preference store that already
 * exists - so NO migration is required and the developer's database is never
 * touched.
 *
 * The hints toggle lives here too: both are per-user interface preferences.
 */
class LocaleController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Language', [
            'current' => $user->locale(),
            'hintsEnabled' => $user->hintsEnabled(),
            'supported' => LocaleManager::catalogue(),
        ]);
    }

    /**
     * Persist the chosen locale. Validation is by whitelist against the
     * configured locales, so an unknown code can never be stored.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(LocaleManager::supported()))],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->setSetting('locale', $data['locale']);

        // Apply immediately so the redirect (and its flash message) already
        // renders in the newly chosen language.
        App::setLocale($data['locale']);

        return redirect()
            ->route('settings.language.edit')
            ->with('success', __('settings.language_saved'));
    }

    /** Toggle the universal in-body hint badges on/off for this user. */
    public function updateHints(Request $request)
    {
        $data = $request->validate([
            'hints_enabled' => ['required', 'boolean'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->setSetting('hints_enabled', (bool) $data['hints_enabled']);

        return redirect()
            ->route('settings.language.edit')
            ->with('success', __('settings.hints_saved'));
    }
}

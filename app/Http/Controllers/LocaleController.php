<?php

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

/**
 * Per-account interface preferences: language + on-screen hints.
 *
 * The UI for BOTH now lives inside the Theme / General settings manager (there
 * is no standalone Language page), so this controller only owns the write
 * endpoints. Both values are stored in `user_settings` - the generic preference
 * store that already exists - so no migration is required and the developer's
 * database is never touched.
 */
class LocaleController extends Controller
{
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
            ->route('settings.theme.edit')
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
            ->route('settings.theme.edit')
            ->with('success', __('settings.hints_saved'));
    }
}

<?php

namespace Tests\Browser\Settings;

use App\Models\Student;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * CENTRALISED LOCALISATION.
 *
 * Language is a per-account preference changed ONLY in Settings → Language (never
 * the top bar). Switching it repaints the whole UI, persists across reloads, and
 * leaves user-generated data (names) untouched.
 */
class MultiLanguagePreferencesTest extends DuskTestCase
{
    use DuskSupport;

    public function test_language_switches_whole_ui_and_persists(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'lang-admin@example.test']);

        // User-generated data that must NEVER be translated.
        Student::create([
            'institution_id' => $institution->id,
            'name' => 'Rafiul Karim',
            'roll' => 'NSU-2201',
            'status' => 'active',
        ]);

        $this->step('InstitutionAdmin', 'Settings/Language', 'switch EN → BN', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginViaForm($browser, $admin);

            $browser->visit('/settings/language')
                ->waitFor('[data-testid=language-option-bn]', 20);

            // Switch to Bengali.
            $browser->click('[data-testid=language-option-bn]');
            $browser->waitFor('[data-testid=language-option-bn][aria-pressed=true]', 20);

            // <html lang> now reflects the choice...
            $lang = $browser->script("return document.documentElement.getAttribute('lang');");
            $this->assertSame('bn', $lang[0] ?? null, 'The document language did not switch to Bengali.');

            // ...and the navigation is translated.
            $browser->assertSee('ড্যাশবোর্ড'); // "Dashboard"

            // The switcher is NOT in the top bar.
            $browser->assertMissing('[data-testid=topbar-language-switcher]');

            // Persists across a full reload (stored on the account).
            $browser->refresh()->waitForText('ড্যাশবোর্ড', 20);
            $browser->assertSee('ড্যাশবোর্ড');

            // User-generated data is untouched: the roster name is unchanged on a
            // Bengali interface.
            $browser->visit('/meals/students')
                ->waitForText('Rafiul Karim', 20)
                ->assertSee('Rafiul Karim');
        });
    }
}

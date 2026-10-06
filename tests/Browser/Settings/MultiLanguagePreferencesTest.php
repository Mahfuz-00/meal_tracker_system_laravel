<?php

namespace Tests\Browser\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * CENTRALISED LOCALISATION (Central Language Hub).
 *
 * Language is a per-account preference edited INSIDE the Theme/General settings
 * manager (the standalone Language page was merged in), never in the top bar.
 * Switching it repaints the whole UI, persists across fresh requests, and leaves
 * user-generated data (a person's name) untouched.
 */
class MultiLanguagePreferencesTest extends DuskTestCase
{
    use DuskSupport;

    public function test_language_switches_whole_ui_and_persists(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, [
            'email' => 'lang-admin@example.test',
            // User-generated data that must NEVER be translated.
            'name' => 'Karim Rahman',
        ]);

        $this->step('InstitutionAdmin', 'Settings/Theme (Language)', 'switch EN → BN', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginViaForm($browser, $admin);

            $browser->visit('/settings/theme')
                ->waitFor('[data-testid=language-option-bn]', 20);

            // The language control lives INSIDE the Theme/General settings manager
            // (merged in), and NOT in the top bar.
            $browser->assertPresent('[data-testid=language-preferences]')
                ->assertMissing('[data-testid=topbar-language-switcher]');

            // Switch to Bengali.
            $browser->click('[data-testid=language-option-bn]');
            $browser->waitFor('[data-testid=language-option-bn][aria-pressed=true]', 20);

            // <html lang> now reflects the choice, and the nav is translated.
            $lang = $browser->script("return document.documentElement.getAttribute('lang');");
            $this->assertSame('bn', $lang[0] ?? null, 'The document language did not switch to Bengali.');
            $browser->assertSee('ড্যাশবোর্ড'); // "Dashboard"

            // Persists across a fresh server request (stored on the account).
            $browser->visit('/dashboard');
            $browser->waitFor('[data-testid=app-sidebar-desktop]', 20);
            $state = $browser->script(
                "var p = JSON.parse(document.getElementById('app').dataset.page);"
                . " return JSON.stringify({current: p.props.locale.current,"
                . " hasBn: document.body.innerText.indexOf('ড্যাশবোর্ড') !== -1});"
            );
            $decoded = json_decode($state[0] ?? '{}', true);
            $this->assertSame('bn', $decoded['current'] ?? null, 'Locale did not persist across requests: '.($state[0] ?? 'null'));
            $this->assertTrue((bool) ($decoded['hasBn'] ?? false), 'Bengali UI missing after a fresh request: '.($state[0] ?? 'null'));

            // User-generated data is untouched: the account holder's own name is
            // shown verbatim (never translated) on the Bengali interface.
            $browser->assertSee('Karim Rahman');
        });
    }
}

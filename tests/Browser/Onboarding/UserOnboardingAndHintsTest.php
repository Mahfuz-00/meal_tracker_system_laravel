<?php

namespace Tests\Browser\Onboarding;

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * UNIVERSAL IN-BODY HELP HINTS.
 *
 * The "?" badges appear across every module, strictly inside the body (never
 * overflowing the viewport), and respect the global toggle in Settings - hiding
 * and reappearing without a page reload.
 */
class UserOnboardingAndHintsTest extends DuskTestCase
{
    use DuskSupport;

    public function test_help_hints_render_across_modules_and_toggle(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'hints-admin@example.test']);

        $this->step('InstitutionAdmin', 'Hints', 'dashboard badges render in-body', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginViaForm($browser, $admin);

            $browser->waitFor('[data-testid=help-hint]', 20)
                ->assertPresent('[data-testid=help-hint]');

            // Every badge must stay within the HORIZONTAL bounds of the viewport
            // (never overflowing or hidden behind the sidebar). Vertical position
            // is not asserted: badges below the fold are legitimately off-screen.
            $overflow = $browser->script(
                "var bad = 0; document.querySelectorAll('[data-testid=help-hint]').forEach(function (h) {"
                . " var r = h.getBoundingClientRect();"
                . " if (r.left < 0 || r.right > window.innerWidth + 1) bad++; });"
                . " return bad;"
            );
            $this->assertSame(0, (int) ($overflow[0] ?? 0), 'A hint badge overflowed the viewport horizontally.');

            // A Meals module page carries hints too (client-side navigation).
            $browser->click('[data-testid=app-sidebar-desktop] a[href$="/meals/entries"]')
                ->waitForLocation('/meals/entries', 20)
                ->waitFor('[data-testid=help-hint]', 20)
                ->assertPresent('[data-testid=help-hint]');

            $this->step('InstitutionAdmin', 'Hints', 'toggle off then on', __LINE__);

            // Toggle hints OFF in Settings → every badge vanishes (no reload).
            $browser->visit('/settings/theme')
                ->waitFor('[data-testid=hints-toggle]', 20)
                ->click('[data-testid=hints-toggle]')
                ->waitUntilMissing('[data-testid=help-hint]', 20)
                ->assertMissing('[data-testid=help-hint]');

            // Toggle back ON → badges return.
            $browser->click('[data-testid=hints-toggle]')
                ->waitFor('[data-testid=help-hint]', 20)
                ->assertPresent('[data-testid=help-hint]');
        });
    }
}

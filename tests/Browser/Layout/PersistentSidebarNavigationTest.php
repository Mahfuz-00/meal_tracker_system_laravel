<?php

namespace Tests\Browser\Layout;

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * THE PERSISTENT SHELL.
 *
 * The global sidebar must be mounted ONCE in the app shell, so moving between
 * modules swaps only the main content body - the sidebar itself never remounts,
 * blinks, or loses its open/closed state.
 *
 * Proven by stamping the sidebar with an attribute React does not manage and
 * confirming the SAME element (same stamp) survives a client-side navigation
 * into another module and back.
 */
class PersistentSidebarNavigationTest extends DuskTestCase
{
    use DuskSupport;

    public function test_sidebar_is_not_remounted_when_switching_modules(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'layout-admin@example.test']);

        $this->step('Layout', 'Sidebar', 'stamp sidebar, navigate dashboard ⇄ meals', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginViaForm($browser, $admin);
            $browser->waitFor('[data-testid=app-sidebar-desktop]', 20);

            // Stamp the live sidebar element. React never renders this attribute,
            // so if the element survives navigation the stamp survives with it.
            $browser->script(
                "document.querySelector('[data-testid=app-sidebar-desktop]').setAttribute('data-mounted-at','SENTINEL-1');"
            );

            // Client-side navigation into the Meals module (an Inertia visit -
            // the sidebar link, NOT a full page load).
            $browser->click('[data-testid=app-sidebar-desktop] a[href$="/meals/entries"]')
                ->waitForLocation('/meals/entries', 20)
                ->waitFor('[data-testid=app-sidebar-desktop]', 20);

            $stamp = $browser->script(
                "return document.querySelector('[data-testid=app-sidebar-desktop]').getAttribute('data-mounted-at');"
            );
            $this->assertSame(
                'SENTINEL-1',
                $stamp[0] ?? null,
                'The sidebar was remounted when navigating from Dashboard into the Meals module.'
            );

            // Its active selection updates in place (no remount needed).
            $browser->assertPresent('[data-testid=app-sidebar-desktop] a[aria-current="page"]');

            // Exactly one sidebar shell exists in the DOM.
            $count = $browser->script(
                "return document.querySelectorAll('[data-testid=app-sidebar-desktop]').length;"
            );
            $this->assertSame(1, (int) ($count[0] ?? 0), 'Expected exactly one desktop sidebar.');

            // And back to Dashboard - still the same element.
            $browser->click('[data-testid=app-sidebar-desktop] a[href$="/dashboard"]')
                ->waitForLocation('/dashboard', 20)
                ->waitFor('[data-testid=app-sidebar-desktop]', 20);

            $stamp2 = $browser->script(
                "return document.querySelector('[data-testid=app-sidebar-desktop]').getAttribute('data-mounted-at');"
            );
            $this->assertSame(
                'SENTINEL-1',
                $stamp2[0] ?? null,
                'The sidebar was remounted on the return navigation.'
            );
        });
    }
}

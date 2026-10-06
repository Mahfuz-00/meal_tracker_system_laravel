<?php

namespace Tests\Browser\Layout;

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * THE PERSISTENT SHELL.
 *
 * The global sidebar AND the top bar must be mounted ONCE in the app shell, so
 * moving between modules swaps only the main content body - neither the sidebar
 * nor the top bar remounts, blinks, or loses state.
 *
 * Proven by stamping each with an attribute React does not manage and confirming
 * the SAME elements (same stamps) survive a client-side navigation into another
 * module and back.
 */
class PersistentSidebarNavigationTest extends DuskTestCase
{
    use DuskSupport;

    public function test_sidebar_and_topbar_persist_across_modules(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'layout-admin@example.test']);

        $this->step('Layout', 'Shell', 'stamp sidebar + top bar, navigate dashboard ⇄ meals', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->loginViaForm($browser, $admin);

            $browser->waitFor('[data-testid=app-sidebar-desktop]', 20)
                ->waitFor('[data-testid=app-topbar]', 20);

            // The Top Bar carries profile actions + notifications + DYNAMIC context.
            $browser->assertPresent('[data-testid=topbar-profile]')
                ->assertPresent('[data-testid=topbar-context]')
                ->assertPresent('[data-testid=topbar-breadcrumb]')
                ->assertPresent('[data-testid=topbar-date]');

            // The breadcrumb reflects the ACTIVE module (dynamic, not hardcoded).
            $dashCrumb = $browser->script("return document.querySelector('[data-testid=topbar-breadcrumb]').textContent;");
            $this->assertStringContainsString('Dashboard', $dashCrumb[0] ?? '', 'Breadcrumb does not show the active module.');

            // Stamp the live shell elements. React never renders these attributes,
            // so if the elements survive navigation the stamps survive with them.
            $browser->script(
                "document.querySelector('[data-testid=app-sidebar-desktop]').setAttribute('data-mounted-at','SIDEBAR-1');"
                . "document.querySelector('[data-testid=app-topbar]').setAttribute('data-mounted-at','TOPBAR-1');"
            );

            // Client-side navigation into the Meals module (an Inertia visit).
            $browser->click('[data-testid=app-sidebar-desktop] a[href$="/meals/entries"]')
                ->waitForLocation('/meals/entries', 20)
                ->waitFor('[data-testid=app-sidebar-desktop]', 20)
                ->waitFor('[data-testid=app-topbar]', 20);

            $sidebarStamp = $browser->script("return document.querySelector('[data-testid=app-sidebar-desktop]').getAttribute('data-mounted-at');");
            $topbarStamp = $browser->script("return document.querySelector('[data-testid=app-topbar]').getAttribute('data-mounted-at');");
            $this->assertSame('SIDEBAR-1', $sidebarStamp[0] ?? null, 'The sidebar was remounted navigating into the Meals module.');
            $this->assertSame('TOPBAR-1', $topbarStamp[0] ?? null, 'The top bar was remounted navigating into the Meals module.');

            // ...and the breadcrumb updates to the new module / sub-module.
            $mealCrumb = $browser->script("return document.querySelector('[data-testid=topbar-breadcrumb]').textContent;");
            $this->assertStringContainsString('Meal', $mealCrumb[0] ?? '', 'Breadcrumb did not update to the Meals module.');

            // Active selection updates in place (no remount needed).
            $browser->assertPresent('[data-testid=app-sidebar-desktop] a[aria-current="page"]');

            // Exactly one of each shell element exists.
            $sidebarCount = $browser->script("return document.querySelectorAll('[data-testid=app-sidebar-desktop]').length;");
            $topbarCount = $browser->script("return document.querySelectorAll('[data-testid=app-topbar]').length;");
            $this->assertSame(1, (int) ($sidebarCount[0] ?? 0), 'Expected exactly one desktop sidebar.');
            $this->assertSame(1, (int) ($topbarCount[0] ?? 0), 'Expected exactly one top bar.');

            // And back to Dashboard - still the same elements.
            $browser->click('[data-testid=app-sidebar-desktop] a[href$="/dashboard"]')
                ->waitForLocation('/dashboard', 20)
                ->waitFor('[data-testid=app-topbar]', 20);

            $sidebarStamp2 = $browser->script("return document.querySelector('[data-testid=app-sidebar-desktop]').getAttribute('data-mounted-at');");
            $topbarStamp2 = $browser->script("return document.querySelector('[data-testid=app-topbar]').getAttribute('data-mounted-at');");
            $this->assertSame('SIDEBAR-1', $sidebarStamp2[0] ?? null, 'The sidebar was remounted on the return navigation.');
            $this->assertSame('TOPBAR-1', $topbarStamp2[0] ?? null, 'The top bar was remounted on the return navigation.');

            // The top bar shows the current page context (ported page header).
            $browser->assertSee('Meal & Expense Overview');
        });
    }
}

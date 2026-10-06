<?php

namespace Tests\Browser\Auth;

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * THE REDESIGNED LOGIN.
 *
 * The login is now a split layout: a light, animated INTRO panel on the left
 * and the form card on the right. There is no SSO/OAuth in this codebase, so the
 * page must degrade gracefully - no SSO block, no error - and the form must
 * still authenticate.
 */
class SsoAndLoginRedesignTest extends DuskTestCase
{
    use DuskSupport;

    public function test_login_page_renders_split_intro_panel_and_form(): void
    {
        $this->step('Guest', 'Login', 'render redesigned split login', __LINE__);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitFor('#email', 20)
                ->assertPresent('[data-testid=login-intro-panel]')
                ->assertPresent('[data-testid=login-form-card]')
                ->assertPresent('#email')
                ->assertPresent('#password');

            // The intro panel carries the Lottie-style looping SVG animation.
            $animated = $browser->script(
                "return !!document.querySelector('[data-testid=login-intro-panel] .lh-hero svg .lh-float');"
            );
            $this->assertTrue(
                (bool) ($animated[0] ?? false),
                'The login intro panel has no Lottie-style animated element.'
            );

            // Branding is CONSISTENT: the intro panel, the form footer and the
            // page <title> all show the same single-sourced platform name.
            $panelText = $browser->script(
                "return document.querySelector('[data-testid=login-intro-panel]').textContent;"
            );
            $this->assertStringContainsString('NomNomytics', (string) ($panelText[0] ?? ''), 'The intro panel brand is inconsistent.');

            $title = $browser->script('return document.title;');
            $this->assertStringContainsString('NomNomytics', (string) ($title[0] ?? ''), 'The page title does not use the platform brand.');

            $cardText = $browser->script(
                "return document.querySelector('[data-testid=login-form-card]').textContent;"
            );
            $this->assertStringContainsString('NomNomytics', (string) ($cardText[0] ?? ''), 'The login card brand is inconsistent.');

            // Graceful absence of SSO when no providers are configured.
            $browser->assertMissing('[data-testid=sso-providers]');
        });
    }

    public function test_redesigned_login_still_authenticates(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'login-check@example.test']);

        $this->step('InstitutionAdmin', 'Login', 'submit the login form', __LINE__);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->visit('/login')
                ->waitFor('#email', 20)
                ->type('#email', $admin->email)
                ->type('#password', 'password')
                ->pause(300);

            // React onSubmit is bound to the form, so submit through the DOM.
            $browser->script("document.querySelector('form').requestSubmit();");

            $browser->waitForLocation('/dashboard', 20)->assertPathIs('/dashboard');
        });
    }
}

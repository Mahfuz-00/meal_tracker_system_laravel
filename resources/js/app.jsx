import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GlobalLoadingIndicator from '@/Components/GlobalLoadingIndicator';
import { FeedbackProvider } from '@/Components/Feedback/FeedbackProvider';
import { HintsProvider } from '@/Components/Help/HintsProvider';
import { applyThemeTokens, readLocalTheme } from '@/Components/ThemeProvider';
import { LocaleProvider } from '@/i18n/LocaleProvider';

let appName = import.meta.env.VITE_APP_NAME || 'NomNomytics';

/**
 * Decide which theme to paint with, in precedence order, and apply it.
 *
 *   1. The signed-in user's DATABASE theme (auth.user.theme) - follows them from
 *      any device.
 *   2. The browser localStorage theme - the PC-local copy (applies pre-login).
 *   3. The institution theme - workspace default.
 *
 * This mirrors ThemeProvider.resolveTheme; running it BEFORE React mounts means
 * there is no flash of the default accent on first paint.
 */
function resolveAndApply(props) {
    const userTheme = props?.auth?.user?.theme;
    const institutionTheme = props?.institution?.theme;

    if (userTheme && Object.keys(userTheme).length > 0) {
        applyThemeTokens(userTheme, { persist: true });
        return;
    }

    const local = readLocalTheme();
    if (local) {
        applyThemeTokens(local, { persist: false });
        return;
    }

    applyThemeTokens(institutionTheme, { persist: false });
}

/*
 * PUBLIC / GUEST PAGES own their own layout (GuestLayout), so they are excluded
 * from the persistent authenticated shell. Everything else gets it.
 */
const GUEST_PAGES = new Set(['Welcome']);
const isGuestPage = (name) => GUEST_PAGES.has(name) || name.startsWith('Auth/');

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const module = await resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );

        const page = module.default;

        /*
         * PERSISTENT LAYOUT.
         *
         * Inertia renders `page.layout(page)` and keeps the LAYOUT component
         * instance mounted across visits - only the page child swaps. Attaching
         * the shell here (once, for every authenticated page) is what stops the
         * sidebar from remounting/blinking on navigation. A page that needs a
         * different shell can declare its own `layout` and this is skipped.
         */
        if (page && !page.layout && !isGuestPage(name)) {
            page.layout = (child) => <AuthenticatedLayout>{child}</AuthenticatedLayout>;
        }

        return module;
    },
    setup({ el, App, props }) {
        // 1. Initial paint: apply the theme before React mounts (no flash).
        resolveAndApply(props?.initialPage?.props);

        /*
         * Single-source the brand. The page <title> must show the SAME software
         * name as the login panel and sidebar - so read it from the shared
         * `platform` prop (config/platform.php) rather than a hardcoded default.
         */
        appName = props?.initialPage?.props?.platform?.name || appName;

        // 2. Re-apply on every successful visit, so a saved theme - or an SSA
        //    institution switch - repaints the whole app at once, no reload.
        router.on('success', (event) => {
            resolveAndApply(event.detail.page.props);
        });

        /*
         * Shared payloads read ONCE for the providers that render OUTSIDE
         * Inertia's <App> context (they cannot call usePage()). They follow
         * later visits themselves via the router.
         */
        const locale = props.initialPage.props?.locale;
        const hints = props.initialPage.props?.hints;

        const root = createRoot(el);

        root.render(
            // FeedbackProvider wraps the whole app (not just a layout) so flash
            // messages and confirmations work identically on every screen,
            // including the guest pages.
            <FeedbackProvider>
                <LocaleProvider locale={locale}>
                    <HintsProvider hints={hints}>
                        <App {...props} />
                        {/* One central spinner for every async request. */}
                        <GlobalLoadingIndicator />
                    </HintsProvider>
                </LocaleProvider>
            </FeedbackProvider>
        );
    },
    progress: {
        // Inertia's thin top bar, kept for fast navigations; the global
        // overlay handles slower requests with a fuller indicator.
        color: 'var(--accent, #4f46e5)',
        showSpinner: false,
    },
});

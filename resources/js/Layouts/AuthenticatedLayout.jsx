import Sidebar from '@/Components/Sidebar';
import ThemeProvider from '@/Components/ThemeProvider';
import NotificationBell from '@/Components/NotificationBell';
import ShellContext from '@/Layouts/shellContext';
import { useTranslation } from '@/i18n/LocaleProvider';
import { Link, usePage } from '@inertiajs/react';
import { useContext, useEffect, useState } from 'react';

/**
 * Responsive application shell.
 *
 * This is the PERSISTENT shell: app.jsx assigns it as the default layout for
 * every authenticated page (`page.layout = p => <AuthenticatedLayout>{p}</…>`),
 * so Inertia keeps ONE instance mounted across client-side navigations and only
 * the page child swaps. The sidebar therefore never blinks, re-fetches or loses
 * its open/closed state when moving between modules.
 *
 * NESTED DEGRADATION
 * ------------------
 * A page may still render its own `<AuthenticatedLayout header=…>`, and
 * MealsLayout / SettingsLayout wrap it too. When one of those renders INSIDE the
 * persistent shell (ShellContext === true) it must NOT build a second shell -
 * it collapses to just its header + content. That is what lets the persistent
 * shell coexist with the existing per-page layouts during the migration.
 */
export default function AuthenticatedLayout({ header, children }) {
    const { auth, institution, tenant } = usePage().props;
    const { t } = useTranslation();
    const user = auth?.user;

    const inShell = useContext(ShellContext);

    const [drawerOpen, setDrawerOpen] = useState(false);

    // Close the drawer whenever the shell re-renders (i.e. a nav link is tapped).
    useEffect(() => {
        setDrawerOpen(false);
    }, [children]);

    // Lock body scroll while the drawer is open on small screens.
    useEffect(() => {
        document.body.style.overflow = drawerOpen ? 'hidden' : '';
        return () => { document.body.style.overflow = ''; };
    }, [drawerOpen]);

    // Already inside the persistent shell: render header + content only.
    if (inShell) {
        return (
            <>
                {header && <header className="mb-4">{header}</header>}
                {children}
            </>
        );
    }

    return (
        <ShellContext.Provider value={true}>
        <ThemeProvider>
        {/* The shell itself reads the theme tokens, so flipping dark mode recolours
            the page background and default text instantly. */}
        <div className="min-h-screen" style={{ backgroundColor: 'var(--bg-color)', color: 'var(--text-primary)' }}>
            <div className="flex min-h-screen">
                {/* Docked sidebar (desktop) */}
                <div className="hidden lg:block lg:flex-shrink-0" data-testid="app-sidebar-desktop">
                    <Sidebar user={user} />
                </div>

                {/* Off-canvas drawer (mobile / tablet) */}
                {drawerOpen && (
                    <div
                        className="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs lg:hidden"
                        onClick={() => setDrawerOpen(false)}
                        aria-hidden="true"
                    />
                )}
                <div
                    className={`fixed inset-y-0 left-0 z-50 transform transition-transform duration-300 lg:hidden ${drawerOpen ? 'translate-x-0' : '-translate-x-full'
                        }`}
                    data-testid="app-sidebar-mobile"
                >
                    <Sidebar user={user} onNavigate={() => setDrawerOpen(false)} />
                </div>

                {/* Main column */}
                <div className="flex min-w-0 flex-1 flex-col overflow-x-hidden">
                    {/* Mobile top bar with drawer trigger */}
                    <div
                        className="sticky top-0 z-30 flex items-center gap-3 border-b px-4 py-3 backdrop-blur lg:hidden"
                        style={{ backgroundColor: 'var(--surface)', borderColor: 'var(--border-color)' }}
                    >
                        <button
                            type="button"
                            onClick={() => setDrawerOpen((open) => !open)}
                            aria-label={t('Open navigation')}
                            aria-expanded={drawerOpen}
                            className="inline-flex h-10 w-10 items-center justify-center rounded-xl border-slate-200 text-slate-600 transition-colors hover:bg-slate-50"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <span className="truncate text-sm font-bold text-slate-800">
                            {institution?.name || t('Meal Management')}
                        </span>
                    </div>

                    <div className="mx-auto w-full max-w-[1600px] px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        {/* Switched-view banner: only shown when a Software Super
                            Admin is inside another institution's workspace, so
                            they can always see WHICH tenant they are in and get
                            back to the global platform view in one click. */}
                        {tenant?.switched && (
                            <div className="mb-4 flex-col gap-2 rounded-xl border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-2 text-sm text-amber-800">
                                    <svg className="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>
                                        {t('Viewing')} <strong className="font-semibold">{institution?.name}</strong> {t('as a switched workspace.')}
                                    </span>
                                </div>
                                <Link
                                    href={route('settings.institutions.exit')}
                                    method="post"
                                    as="button"
                                    className="inline-flex items-center gap-1.5 self-start rounded-lg bg-amber-600 px-3.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-amber-700 sm:self-auto"
                                >
                                    {t('Exit to platform view')}
                                </Link>
                            </div>
                        )}

                        {/* Header row: page title on the left, the notification
                            bell pinned right so it is reachable from every page
                            and every screen size. */}
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div className="min-w-0 flex-1">
                                {header && (
                                    <header>
                                        <div className="max-w-full">{header}</div>
                                    </header>
                                )}
                            </div>
                            <div className="flex-shrink-0">
                                <NotificationBell />
                            </div>
                        </div>

                        {/* The page child swaps here on navigation; the shell
                            (sidebar, header, bell) is never remounted. */}
                        <main className="animate-page-in">
                            {children}
                        </main>
                    </div>
                </div>
            </div>
        </div>
        </ThemeProvider>
        </ShellContext.Provider>
    );
}

import ApplicationLogo from '@/Components/ApplicationLogo';
import AmbientBackground from '@/Components/AmbientBackground';
import usePlatformBranding from '@/Utils/usePlatformBranding';
import { useTranslation } from '@/i18n/LocaleProvider';
import { Link } from '@inertiajs/react';

/**
 * Guest / auth shell.
 *
 * A two-column split: a light, modern INTRO panel on the left (brand, promise,
 * feature bullets and a gently animated visual) and the form card on the right.
 * The left panel is hidden below `lg`, so small screens keep the focused
 * single-card layout.
 *
 * The subtle background motion comes from the shared AmbientBackground (drifting
 * gradient orbs, `prefers-reduced-motion` guarded) plus the existing `wa-*`
 * entrance/float utilities - no new dependency.
 */
export default function GuestLayout({ heading, subheading, children }) {
    const { name, tagline, logoUrl } = usePlatformBranding();
    const { t } = useTranslation();

    const features = [
        { text: t('auth.feature_ledger'), delay: 'wa-delay-1' },
        { text: t('auth.feature_roles'), delay: 'wa-delay-2' },
        { text: t('auth.feature_insights'), delay: 'wa-delay-3' },
    ];

    return (
        <div className="relative min-h-screen selection:bg-indigo-500 selection:text-white">
            <AmbientBackground />

            <div className="relative z-10 grid min-h-screen lg:grid-cols-2">
                {/* ---- Left intro panel (desktop only) ---- */}
                <aside
                    data-testid="login-intro-panel"
                    className="relative hidden overflow-hidden border-r border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-indigo-50/60 p-12 lg:flex lg:flex-col lg:justify-between"
                >
                    {/* Drifting glow orbs for depth (animated, GPU-only). */}
                    <div aria-hidden="true" className="wa-pulse pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-indigo-300/30 blur-3xl" />
                    <div aria-hidden="true" className="wa-pulse pointer-events-none absolute -bottom-28 -left-16 h-80 w-80 rounded-full bg-sky-300/25 blur-3xl" style={{ animationDelay: '1.4s' }} />

                    <Link href="/" className="relative flex items-center gap-3">
                        {logoUrl ? (
                            <img src={logoUrl} alt={name} className="h-11 w-11 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60" />
                        ) : (
                            <ApplicationLogo className="h-11 w-11 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60" />
                        )}
                        <span className="text-lg font-extrabold tracking-tight text-slate-900">{name}</span>
                    </Link>

                    <div className="wa-rise relative max-w-lg">
                        <h2 className="text-3xl font-extrabold leading-tight tracking-tight text-slate-900">
                            {t('auth.intro_title')}
                        </h2>
                        <p className="mt-4 text-sm leading-relaxed text-slate-600">{t('auth.intro_body')}</p>

                        <ul className="mt-8 space-y-3">
                            {features.map((feature) => (
                                <li key={feature.text} className={`wa-rise ${feature.delay} flex items-center gap-3 text-sm font-semibold text-slate-700`}>
                                    <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[var(--accent-soft)] text-[var(--accent)]">
                                        <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    {feature.text}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* A floating, animated product chip so the panel feels alive. */}
                    <div className="wa-float relative">
                        <div className="max-w-xs rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-xl backdrop-blur">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-slate-900">{t('dashboard.pool_balance')}</span>
                                <span className="rounded-full bg-[var(--accent-soft)] px-2.5 py-0.5 text-[10px] font-bold text-[var(--accent)]">
                                    {t('Live')}
                                </span>
                            </div>
                            <div className="mt-3 h-2 w-2/3 rounded-full bg-slate-100" />
                            <div className="mt-2 h-2 w-1/2 rounded-full bg-slate-100" />
                            <p className="mt-3 text-[11px] leading-relaxed text-slate-500">{tagline}</p>
                        </div>
                    </div>
                </aside>

                {/* ---- Right form column ---- */}
                <main className="flex items-center justify-center px-4 py-12 sm:px-6">
                    <div className="w-full sm:max-w-md">
                        <div
                            data-testid="login-form-card"
                            className="wa-rise relative rounded-3xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5"
                        >
                            {/* Brand logo header (mobile only - the desktop panel shows it). */}
                            <div className="mb-6 flex justify-center lg:hidden">
                                <Link href="/" className="group flex items-center gap-3">
                                    {logoUrl ? (
                                        <img src={logoUrl} alt={name} className="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60 transition-transform group-hover:scale-105" />
                                    ) : (
                                        <ApplicationLogo className="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60 transition-transform group-hover:scale-105" />
                                    )}
                                </Link>
                            </div>

                            {heading && (
                                <div className="mb-6 text-center">
                                    <h1 className="text-xl font-bold tracking-tight text-slate-900">{heading}</h1>
                                    {subheading && <p className="mt-1.5 text-xs text-slate-500">{subheading}</p>}
                                </div>
                            )}

                            {children}
                        </div>

                        <p className="mt-8 text-center text-xs font-medium text-slate-400">
                            &copy; {new Date().getFullYear()} {name} — {t('Secure Multi-tenant Ledger.')}
                        </p>
                    </div>
                </main>
            </div>
        </div>
    );
}

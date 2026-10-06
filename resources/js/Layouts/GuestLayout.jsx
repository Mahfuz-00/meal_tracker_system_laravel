import ApplicationLogo from '@/Components/ApplicationLogo';
import AmbientBackground from '@/Components/AmbientBackground';
import LoginHeroAnimation from '@/Components/LoginHeroAnimation';
import usePlatformBranding from '@/Utils/usePlatformBranding';
import { useTranslation } from '@/i18n/LocaleProvider';
import { Link } from '@inertiajs/react';

/**
 * Guest / auth shell.
 *
 * A two-column split: a light, modern INTRO panel on the left (brand, promise,
 * feature bullets and a smooth Lottie-style vector animation) and the form card
 * on the right. The left panel is hidden below `lg`, so small screens keep the
 * focused single-card layout.
 *
 * BRANDING is single-sourced from config/platform.php via usePlatformBranding(),
 * so the brand shown here is the SAME as the page <title>, the sidebar header
 * and the platform chrome - never a mismatched "Laravel"/tenant name.
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
                    className="relative hidden overflow-hidden border-r border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-indigo-50/60 p-12 lg:flex lg:flex-col"
                >
                    <Link href="/" className="relative flex items-center gap-3">
                        {logoUrl ? (
                            <img src={logoUrl} alt={name} className="h-11 w-11 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60" />
                        ) : (
                            <ApplicationLogo className="h-11 w-11 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60" />
                        )}
                        <span className="text-lg font-extrabold tracking-tight text-slate-900">{name}</span>
                    </Link>

                    <div className="wa-rise relative mt-14 max-w-lg lg:mt-20">
                        <h2 className="text-3xl font-extrabold leading-tight tracking-tight text-slate-900">
                            {t('auth.intro_title')}
                        </h2>
                        <p className="mt-5 text-sm leading-relaxed text-slate-600">{t('auth.intro_body')}</p>

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

                    {/* Lottie-style looping vector animation (plain SVG + CSS). */}
                    <div className="relative mt-auto pt-10">
                        <LoginHeroAnimation />
                        <p className="mt-4 max-w-md text-[11px] leading-relaxed text-slate-500">{tagline}</p>
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
                            <div className="mb-6 flex flex-col items-center gap-2 lg:hidden">
                                <Link href="/" className="group flex items-center gap-3">
                                    {logoUrl ? (
                                        <img src={logoUrl} alt={name} className="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60 transition-transform group-hover:scale-105" />
                                    ) : (
                                        <ApplicationLogo className="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-slate-200/60 transition-transform group-hover:scale-105" />
                                    )}
                                    <span className="text-base font-extrabold tracking-tight text-slate-900">{name}</span>
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

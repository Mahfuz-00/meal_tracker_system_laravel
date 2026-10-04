import React from 'react';
import SettingsLayout from '@/Layouts/SettingsLayout';
import HelpHint from '@/Components/Help/HelpHint';
import { useTranslation } from '@/i18n/LocaleProvider';
import { Head, router } from '@inertiajs/react';

/**
 * Settings → Language & preferences.
 *
 * The ONLY place a language can be changed (never the top bar). Both controls
 * here are per-account preferences: changing one writes `user_settings` and
 * redirects back, so the whole UI repaints in the same round-trip. User-entered
 * data (names, notes) is never translated.
 */
export default function Language({ current, hintsEnabled = true, supported = [], processing = false }) {
    const { t } = useTranslation();

    const choose = (code) => {
        if (code === current) return;
        router.put(route('settings.language.update'), { locale: code }, { preserveScroll: true });
    };

    const toggleHints = () => {
        router.put(route('settings.language.hints'), { hints_enabled: !hintsEnabled }, { preserveScroll: true });
    };

    return (
        <SettingsLayout title={t('settings.title')}>
            <Head title={t('settings.language_title')} />

            <div className="space-y-6">
                {/* ---- Language ---- */}
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7">
                    <div className="border-b border-slate-100 pb-4">
                        <div className="flex items-center">
                            <h3 className="text-base font-bold text-slate-900">{t('settings.language_heading')}</h3>
                            <HelpHint hintKey="hints.language" />
                        </div>
                        <p className="mt-0.5 text-xs text-slate-500">{t('settings.language_subtitle')}</p>
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-2 sm:max-w-xl">
                        {supported.map((option) => {
                            const active = option.code === current;

                            return (
                                <button
                                    key={option.code}
                                    type="button"
                                    data-testid={`language-option-${option.code}`}
                                    aria-pressed={active}
                                    onClick={() => choose(option.code)}
                                    className={`flex items-center justify-between rounded-xl border p-4 text-left transition-all ${active
                                            ? 'border-indigo-300 bg-indigo-50/40 ring-2 ring-indigo-500/10'
                                            : 'border-slate-200/80 bg-white hover:border-slate-300 hover:bg-slate-50/50'
                                        }`}
                                >
                                    <span>
                                        <span className="block text-sm font-bold text-slate-900">{option.label}</span>
                                        <span className="mt-0.5 block text-xs text-slate-500">{option.english}</span>
                                    </span>
                                    {active && (
                                        <svg className="h-4 w-4 flex-shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <p className="mt-4 text-xs leading-relaxed text-slate-500">{t('settings.language_help')}</p>
                </section>

                {/* ---- Hints ---- */}
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:p-7">
                    <div className="border-b border-slate-100 pb-4">
                        <div className="flex items-center">
                            <h3 className="text-base font-bold text-slate-900">{t('settings.hints_heading')}</h3>
                        </div>
                        <p className="mt-0.5 text-xs text-slate-500">{t('settings.hints_subtitle')}</p>
                    </div>

                    <div className="mt-5 flex items-center justify-between gap-4">
                        <span className="text-sm font-semibold text-slate-700">{t('settings.hints_toggle')}</span>
                        <button
                            type="button"
                            role="switch"
                            data-testid="hints-toggle"
                            aria-checked={hintsEnabled}
                            onClick={toggleHints}
                            className={`relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors ${hintsEnabled ? 'bg-[var(--accent)]' : 'bg-slate-300'}`}
                        >
                            <span
                                className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${hintsEnabled ? 'translate-x-5' : 'translate-x-0.5'}`}
                            />
                        </button>
                    </div>

                    <p className="mt-4 text-xs leading-relaxed text-slate-500">{t('settings.hints_help')}</p>
                </section>
            </div>
        </SettingsLayout>
    );
}

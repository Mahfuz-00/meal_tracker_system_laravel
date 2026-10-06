import React from 'react';
import MealsLayout from '@/Layouts/MealsLayout';
import useTerminology from '@/Utils/useTerminology';
import { Head, router } from '@inertiajs/react';

/**
 * MANAGER VOTING & SUGGESTIONS BOARD.
 *
 * Live vote tallies for the current week's admin-configured options, plus every
 * meal suggestion members have submitted (pending first).
 */
export default function Index({ week = '', options = [], suggestions = [] }) {
    const { t } = useTerminology();

    const totalVotes = options.reduce((sum, o) => sum + (o.votes || 0), 0);
    const changeWeek = (value) => {
        router.get(route('meals.voting.index'), { week: value }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <MealsLayout
            title="Voting & Suggestions"
            description="What members want to eat - live vote tallies and their suggestions."
        >
            <Head title="Voting & Suggestions" />

            <div className="flex flex-col gap-3 rounded-xl border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <label htmlFor="vote-week" className="mb-1.5 block text-sm font-semibold text-slate-700">Voting week (starts)</label>
                    <input id="vote-week" type="date" value={week} onChange={(e) => changeWeek(e.target.value)}
                        className="rounded-lg border-slate-300 text-sm text-slate-900 focus:border-[var(--accent)] focus:ring-[var(--accent-ring)]" />
                </div>
                <span className="rounded-lg bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-600">{totalVotes} total vote{totalVotes === 1 ? '' : 's'}</span>
            </div>

            {/* Tallies */}
            <div className="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-4">
                    <h3 className="text-base font-bold text-slate-900">Vote tallies</h3>
                    <p className="text-xs text-slate-500">Options are configured in Settings → Meal Voting.</p>
                </div>
                {options.length > 0 ? (
                    <ul className="divide-y divide-slate-100">
                        {options.map((o) => {
                            const pct = totalVotes > 0 ? Math.round((o.votes / totalVotes) * 100) : 0;
                            return (
                                <li key={o.id} className="px-6 py-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="font-semibold text-slate-900">{o.label}</span>
                                                {!o.is_active && <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500">Inactive</span>}
                                            </div>
                                            <div className="mt-2 h-2 w-full max-w-md overflow-hidden rounded-full bg-slate-100">
                                                <div className="h-full rounded-full bg-[var(--accent)]" style={{ width: `${pct}%` }} />
                                            </div>
                                        </div>
                                        <div className="flex-shrink-0 text-right">
                                            <div className="text-lg font-extrabold text-slate-900">{o.votes}</div>
                                            <div className="text-[11px] text-slate-400">{pct}%</div>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <p className="px-6 py-10 text-center text-sm text-slate-400">No voting options configured yet.</p>
                )}
            </div>

            {/* Suggestions */}
            <div className="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-4">
                    <h3 className="text-base font-bold text-slate-900">Member suggestions</h3>
                    <p className="text-xs text-slate-500">Newest first; pending at the top.</p>
                </div>
                {suggestions.length > 0 ? (
                    <ul className="divide-y divide-slate-100">
                        {suggestions.map((s) => (
                            <li key={s.id} className="px-6 py-4">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-semibold text-slate-900">{s.title}</span>
                                    <span className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase ${s.status === 'reviewed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>{s.status}</span>
                                    <span className="text-xs text-slate-400">{s.student}{s.roll ? ` (${s.roll})` : ''} · {s.created_at}</span>
                                </div>
                                {s.body && <p className="mt-1 text-sm text-slate-600">{s.body}</p>}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="px-6 py-10 text-center text-sm text-slate-400">
                        No suggestions from your {t('members', 'members').toLowerCase()} yet.
                    </p>
                )}
            </div>
        </MealsLayout>
    );
}

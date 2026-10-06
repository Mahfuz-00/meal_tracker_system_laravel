import React from 'react';
import MealsLayout from '@/Layouts/MealsLayout';
import useTerminology from '@/Utils/useTerminology';
import { Head, router } from '@inertiajs/react';

/**
 * MANAGER MEAL SCHEDULES BOARD.
 *
 * Shows everyone who is OFF (or back ON) for a chosen day, applying each
 * schedule's recurrence, plus the full list of active schedules. Members submit
 * these from their portal; the board reflects them on the next visit.
 */
export default function Index({ schedules = [], date = '', onDate = [] }) {
    const { t } = useTerminology();

    const changeDate = (value) => {
        router.get(route('meals.schedules.index'), { date: value }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const off = onDate.filter((r) => !r.taking_meals);
    const on = onDate.filter((r) => r.taking_meals);

    return (
        <MealsLayout
            title="Meal Schedules"
            description="Who has told you they will or won't take meals, for any day."
        >
            <Head title="Meal Schedules" />

            {/* Date picker */}
            <div className="flex flex-col gap-3 rounded-xl border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <label htmlFor="schedule-date" className="mb-1.5 block text-sm font-semibold text-slate-700">Show for date</label>
                    <input id="schedule-date" type="date" value={date} onChange={(e) => changeDate(e.target.value)}
                        className="rounded-lg border-slate-300 text-sm text-slate-900 focus:border-[var(--accent)] focus:ring-[var(--accent-ring)]" />
                </div>
                <div className="flex gap-3 text-sm">
                    <span className="rounded-lg bg-amber-50 px-3 py-1.5 font-semibold text-amber-700">{off.length} skipping</span>
                    <span className="rounded-lg bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700">{on.length} taking</span>
                </div>
            </div>

            {/* Who is off / on for the selected day */}
            <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div className="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-5 py-3">
                        <h4 className="text-sm font-bold text-slate-900">Skipping meals</h4>
                        <p className="text-[11px] text-slate-500">{t('members', 'Members')} who won't eat on {date}</p>
                    </div>
                    {off.length > 0 ? (
                        <ul className="divide-y divide-slate-100">
                            {off.map((r, i) => (
                                <li key={i} className="flex items-center justify-between px-5 py-3 text-sm">
                                    <span className="font-semibold text-slate-800">{r.student}{r.roll ? ` (${r.roll})` : ''}</span>
                                    <span className="text-xs text-slate-500">{r.meals.join(', ')}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-5 py-8 text-center text-xs text-slate-400">Nobody has opted out for this day.</p>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-5 py-3">
                        <h4 className="text-sm font-bold text-slate-900">Taking meals</h4>
                        <p className="text-[11px] text-slate-500">Confirmed for {date}</p>
                    </div>
                    {on.length > 0 ? (
                        <ul className="divide-y divide-slate-100">
                            {on.map((r, i) => (
                                <li key={i} className="flex items-center justify-between px-5 py-3 text-sm">
                                    <span className="font-semibold text-slate-800">{r.student}{r.roll ? ` (${r.roll})` : ''}</span>
                                    <span className="text-xs text-slate-500">{r.meals.join(', ')}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-5 py-8 text-center text-xs text-slate-400">No confirmations for this day.</p>
                    )}
                </div>
            </div>

            {/* All active schedules */}
            <div className="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-4">
                    <h3 className="text-base font-bold text-slate-900">All active schedules</h3>
                    <p className="text-xs text-slate-500">Every rule members currently have in effect.</p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                <th className="px-6 py-3">{t('member', 'Member')}</th>
                                <th className="px-6 py-3">Window</th>
                                <th className="px-6 py-3">Repeats</th>
                                <th className="px-6 py-3">Meals</th>
                                <th className="px-6 py-3">Intent</th>
                                <th className="px-6 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-sm">
                            {schedules.length > 0 ? schedules.map((s) => (
                                <tr key={s.id} className="transition-colors hover:bg-slate-50/60">
                                    <td className="px-6 py-3 font-semibold text-slate-900">{s.student}{s.roll ? ` (${s.roll})` : ''}</td>
                                    <td className="px-6 py-3 text-slate-600">{s.start_date}{s.end_date && s.end_date !== s.start_date ? ` → ${s.end_date}` : ''}</td>
                                    <td className="px-6 py-3 text-slate-600">{s.recurrence_label}{s.recurrence === 'interval' && s.interval_days ? ` (${s.interval_days}d)` : ''}</td>
                                    <td className="px-6 py-3 text-slate-600">{s.meals.join(', ')}</td>
                                    <td className="px-6 py-3">
                                        <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-semibold ${s.taking_meals ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>{s.taking_meals ? 'Taking' : 'Skipping'}</span>
                                    </td>
                                    <td className="px-6 py-3 text-xs text-slate-500">{s.note || '—'}</td>
                                </tr>
                            )) : (
                                <tr><td colSpan="6" className="py-14 text-center text-xs text-slate-400">No schedules yet.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </MealsLayout>
    );
}

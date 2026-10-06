import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import { useFeedback } from '@/Components/Feedback/FeedbackProvider';
import { Head, router, useForm } from '@inertiajs/react';

/**
 * MEMBER MEAL SCHEDULING.
 *
 * The member tells the manager whether they WILL or WON'T take meals on a date
 * or range, optionally repeating (one time / daily / weekly / every N days).
 * The manager sees this on the Meal Schedules board immediately.
 */
const EMPTY = {
    start_date: '',
    end_date: '',
    recurrence: 'one_time',
    interval_days: '',
    taking_meals: false,
    breakfast: true,
    lunch: true,
    dinner: true,
    note: '',
};

export default function Schedule({ hasMemberRecord = true, schedules = [], recurrences = [] }) {
    const { confirm } = useFeedback();
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ ...EMPTY });

    const openModal = () => {
        clearErrors();
        reset();
        setData({ ...EMPTY });
        setOpen(true);
    };

    const close = () => {
        setOpen(false);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('member.schedule.store'), { preserveScroll: true, onSuccess: () => close() });
    };

    const cancel = async (schedule) => {
        const ok = await confirm({
            title: 'Cancel this schedule?',
            message: 'The manager will no longer see it as active.',
            tone: 'danger',
            confirmLabel: 'Cancel schedule',
        });
        if (!ok) return;
        router.delete(route('member.schedule.destroy', schedule.id), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Meal Schedule</h2>
                        <p className="mt-0.5 text-xs font-medium text-slate-500">
                            Tell the manager which meals you will or won't take.
                        </p>
                    </div>
                    {hasMemberRecord && (
                        <button type="button" onClick={openModal}
                            className="inline-flex items-center gap-2 self-start rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                            New Schedule
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Meal Schedule" />

            {!hasMemberRecord ? (
                <div className="rounded-2xl border-slate-200 bg-white p-10 text-center shadow-sm">
                    <h3 className="text-base font-bold text-slate-800">Your account is not linked yet</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Scheduling needs a member record. Ask your manager to link your account.
                    </p>
                </div>
            ) : (
                <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-6 py-4">
                        <h3 className="text-base font-bold text-slate-900">Your schedules</h3>
                        <p className="text-xs text-slate-500">Active and cancelled requests you have sent.</p>
                    </div>
                    {schedules.length > 0 ? (
                        <ul className="divide-y divide-slate-100">
                            {schedules.map((s) => (
                                <li key={s.id} className="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${s.taking_meals ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-amber-100 bg-amber-50 text-amber-700'}`}>
                                                {s.taking_meals ? 'Will take' : 'Will skip'}
                                            </span>
                                            <span className="text-sm font-semibold text-slate-800">{s.meals.join(' · ')}</span>
                                            {s.status !== 'active' && (
                                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500">Cancelled</span>
                                            )}
                                        </div>
                                        <div className="mt-1 text-xs text-slate-500">
                                            {s.start_date}{s.end_date && s.end_date !== s.start_date ? ` → ${s.end_date}` : ''} · {s.recurrence_label}
                                            {s.recurrence === 'interval' && s.interval_days ? ` (every ${s.interval_days} days)` : ''}
                                        </div>
                                        {s.note && <p className="mt-1 text-xs text-slate-400">{s.note}</p>}
                                    </div>
                                    {s.status === 'active' && (
                                        <button type="button" onClick={() => cancel(s)}
                                            className="flex-shrink-0 rounded-lg border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                            Cancel
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="py-14 text-center">
                            <p className="text-sm font-semibold text-slate-600">No schedules yet.</p>
                            <p className="mt-1 text-xs text-slate-400">Going away? Tell the manager so meals aren't counted.</p>
                            <button type="button" onClick={openModal}
                                className="mt-4 inline-flex items-center gap-2 rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white transition-opacity hover:opacity-90">
                                Create your first schedule
                            </button>
                        </div>
                    )}
                </div>
            )}

            <Modal
                open={open}
                onClose={close}
                title="Meal Schedule"
                description="Tell the manager whether you will take the selected meals."
                maxWidth="max-w-xl"
                footer={
                    <>
                        <button type="button" onClick={close} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="schedule-form" disabled={processing}
                            className="inline-flex items-center gap-2 rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Sending...' : 'Send to manager'}
                        </button>
                    </>
                }
            >
                <form id="schedule-form" onSubmit={submit} className="space-y-4">
                    <Field
                        label="Will you take these meals?"
                        name="taking_meals"
                        type="select"
                        value={data.taking_meals ? '1' : '0'}
                        error={errors.taking_meals}
                        options={[{ value: '0', label: "No - I won't take them" }, { value: '1', label: 'Yes - I will take them' }]}
                        onChange={(e) => setData('taking_meals', e.target.value === '1')}
                    />

                    <div className="rounded-lg border-slate-200 bg-slate-50 p-4">
                        <p className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Which meals</p>
                        <div className="grid grid-cols-3 gap-3">
                            {[{ k: 'breakfast', l: 'Breakfast' }, { k: 'lunch', l: 'Lunch' }, { k: 'dinner', l: 'Dinner' }].map((m) => (
                                <label key={m.k} className="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" checked={Boolean(data[m.k])}
                                        onChange={(e) => setData(m.k, e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-[var(--accent)] focus:ring-[var(--accent)]" />
                                    {m.l}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="From" name="start_date" type="date" required value={data.start_date} error={errors.start_date}
                            onChange={(e) => setData('start_date', e.target.value)} />
                        <Field label="To (optional)" name="end_date" type="date" value={data.end_date} error={errors.end_date}
                            onChange={(e) => setData('end_date', e.target.value)} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Repeats" name="recurrence" type="select" value={data.recurrence} error={errors.recurrence}
                            options={recurrences} onChange={(e) => setData('recurrence', e.target.value)} />
                        {data.recurrence === 'interval' && (
                            <Field label="Every N days" name="interval_days" type="number" min="1" max="60" value={data.interval_days} error={errors.interval_days}
                                onChange={(e) => setData('interval_days', e.target.value)} />
                        )}
                    </div>

                    <Field label="Note for the manager" name="note" value={data.note} error={errors.note}
                        placeholder="Optional (e.g. going home for the weekend)" onChange={(e) => setData('note', e.target.value)} />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

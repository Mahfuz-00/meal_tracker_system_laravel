import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import { Head, Link, useForm } from '@inertiajs/react';

/**
 * MEAL CLAIMS (inside the Meal & Schedule module).
 *
 * Reports a wrong MEAL COUNT on a specific date - either meals missed but not
 * counted (add back) or meals counted while off/absent (remove). A claim can
 * only be raised for a date that actually has a recorded meal entry.
 */
const EMPTY = {
    kind: 'dispute',
    subject: 'meal',
    entry_date: '',
    meal_direction: 'add',
    breakfast: 0,
    lunch: 0,
    dinner: 0,
    title: '',
    description: '',
};

function StatusChip({ status, label }) {
    const tone = {
        pending: 'border-amber-100 bg-amber-50 text-amber-700',
        approved: 'border-emerald-100 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-100 bg-rose-50 text-rose-700',
    }[status] || 'border-slate-200 bg-slate-100 text-slate-500';

    return <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${tone}`}>{label}</span>;
}

export default function MealClaims({ hasMemberRecord = true, claims, mealDirections = [], mealEntryDates = [] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ ...EMPTY });

    const rows = claims?.data || [];
    const dateRecorded = mealEntryDates.includes(data.entry_date);
    const mealDateBlocked = data.entry_date !== '' && !dateRecorded;
    const mealCount = Number(data.breakfast || 0) + Number(data.lunch || 0) + Number(data.dinner || 0);

    const openModal = () => { clearErrors(); reset(); setData({ ...EMPTY }); setOpen(true); };
    const close = () => { setOpen(false); reset(); };
    const submit = (e) => { e.preventDefault(); post(route('claims.store'), { preserveScroll: true, onSuccess: () => close() }); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Meal Claims</h2>
                        <p className="mt-0.5 text-xs font-medium text-slate-500">Report missing or wrongly-counted meals for a date you were marked for.</p>
                    </div>
                    {hasMemberRecord && (
                        <button type="button" onClick={openModal}
                            className="inline-flex items-center gap-2 self-start rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                            New meal claim
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Meal Claims" />

            {!hasMemberRecord ? (
                <div className="rounded-2xl border-slate-200 bg-white p-10 text-center shadow-sm">
                    <h3 className="text-base font-bold text-slate-800">Your account is not linked yet</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">Meal claims need a member record.</p>
                </div>
            ) : (
                <div className="space-y-5">
                    <div className="rounded-xl border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-500">
                        <strong className="font-semibold text-slate-700">Meal counts only.</strong> Money you spent out-of-pocket belongs in the
                        {' '}<Link href={route('expense-claims.index')} className="font-semibold text-[var(--accent)] hover:underline">Expense Claims</Link>{' '}
                        module.
                    </div>

                    <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-100 px-6 py-4">
                            <h3 className="text-base font-bold text-slate-900">Your meal claims</h3>
                        </div>
                        {rows.length > 0 ? (
                            <ul className="divide-y divide-slate-100">
                                {rows.map((claim) => (
                                    <li key={claim.id} className="flex items-start justify-between gap-3 px-6 py-4">
                                        <div className="min-w-0">
                                            <div className="font-semibold text-slate-900">{claim.title}</div>
                                            <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-400">
                                                <span>{claim.meal_direction === 'remove' ? 'Remove (over-counted)' : 'Add back (missed)'}</span>
                                                {claim.entry_date && <span>For {claim.entry_date}</span>}
                                                <span>Submitted {claim.created_at}</span>
                                            </div>
                                            {claim.review_notes && (
                                                <p className="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                                    <strong className="font-semibold">Manager note:</strong> {claim.review_notes}
                                                </p>
                                            )}
                                        </div>
                                        <StatusChip status={claim.status} label={claim.status_label} />
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="py-14 text-center">
                                <p className="text-sm font-semibold text-slate-600">No meal claims yet.</p>
                                <p className="mt-1 text-xs text-slate-400">Disagree with a recorded meal count? Raise a claim.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            <Modal
                open={open}
                onClose={close}
                title="New meal claim"
                description="Only dates with a recorded meal entry can be disputed."
                maxWidth="max-w-xl"
                footer={
                    <>
                        <button type="button" onClick={close} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="meal-claim-form" disabled={processing || mealDateBlocked}
                            className="rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Submitting...' : 'Submit claim'}
                        </button>
                    </>
                }
            >
                <form id="meal-claim-form" onSubmit={submit} className="space-y-4">
                    <Field label="Date" name="entry_date" type="date" required value={data.entry_date} error={errors.entry_date}
                        onChange={(e) => setData('entry_date', e.target.value)} />

                    {mealDateBlocked && (
                        <p role="alert" className="rounded-lg border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-600">
                            No meal was recorded for this date, so it can&apos;t be disputed.
                        </p>
                    )}
                    {data.entry_date && dateRecorded && (
                        <p className="text-[11px] font-medium text-emerald-600">A meal is recorded for this date - you can dispute it.</p>
                    )}

                    <Field label="What went wrong?" name="meal_direction" type="select" value={data.meal_direction} error={errors.meal_direction}
                        options={(mealDirections || []).map((d) => ({ value: d.value, label: d.label }))}
                        onChange={(e) => setData('meal_direction', e.target.value)} />

                    <div className="rounded-lg border-slate-200 bg-slate-50 p-4">
                        <p className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                            {data.meal_direction === 'remove' ? 'Meals to remove' : 'Meals to add back'}
                        </p>
                        <div className="grid grid-cols-3 gap-3">
                            {[{ k: 'breakfast', l: 'Breakfast' }, { k: 'lunch', l: 'Lunch' }, { k: 'dinner', l: 'Dinner' }].map((m) => (
                                <Field key={m.k} label={m.l} name={m.k} type="number" min={0} max={10} value={data[m.k]} error={errors[m.k]}
                                    onChange={(e) => setData(m.k, Number(e.target.value))} />
                            ))}
                        </div>
                        <p className="mt-2 text-[11px] text-slate-400">{mealCount} meal{mealCount === 1 ? '' : 's'} selected.</p>
                    </div>

                    <Field label="Summary" name="title" required value={data.title} error={errors.title}
                        placeholder="e.g. Breakfast was counted while I was away" onChange={(e) => setData('title', e.target.value)} />
                    <Field label="Details" name="description" type="textarea" value={data.description} error={errors.description}
                        placeholder="Explain what happened..." onChange={(e) => setData('description', e.target.value)} />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

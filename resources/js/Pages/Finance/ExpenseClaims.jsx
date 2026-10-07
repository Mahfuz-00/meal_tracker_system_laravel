import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import useMoney from '@/Utils/useMoney';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

/**
 * EXPENSE CLAIMS (the FINANCE module - member side).
 *
 * Out-of-pocket purchases a member made for the mess/institute, plus
 * missing-deposit corrections. Kept entirely separate from MEAL-count claims
 * (which live in the Meal & Schedule module). On approval the amount can be
 * credited to the member's money-in balance.
 */
const EMPTY = {
    kind: 'expense',
    subject: 'deposit',
    amount: '',
    title: '',
    description: '',
    claim_date: '',
    payment_method: 'Cash',
    credit_to_balance: true,
};

function StatusChip({ status, label }) {
    const tone = {
        pending: 'border-amber-100 bg-amber-50 text-amber-700',
        approved: 'border-emerald-100 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-100 bg-rose-50 text-rose-700',
    }[status] || 'border-slate-200 bg-slate-100 text-slate-500';

    return <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${tone}`}>{label}</span>;
}

export default function ExpenseClaims({ hasMemberRecord = true, claims, kinds = [], subjects = [], paymentMethods = [] }) {
    const money = useMoney();
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ ...EMPTY });
    const rows = claims?.data || [];

    const isExpense = data.kind === 'expense';

    const openModal = () => { clearErrors(); reset(); setData({ ...EMPTY }); setOpen(true); };
    const close = () => { setOpen(false); reset(); };
    const submit = (e) => { e.preventDefault(); post(route('claims.store'), { preserveScroll: true, onSuccess: () => close() }); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Expense Claims</h2>
                        <p className="mt-0.5 text-xs font-medium text-slate-500">Money you spent for the institution, and missing deposits.</p>
                    </div>
                    {hasMemberRecord && (
                        <button type="button" onClick={openModal}
                            className="inline-flex items-center gap-2 self-start rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                            New claim
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Expense Claims" />

            {flash?.success && (
                <div role="status" className="mb-4 rounded-lg border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{flash.success}</div>
            )}

            {!hasMemberRecord ? (
                <div className="rounded-2xl border-slate-200 bg-white p-10 text-center shadow-sm">
                    <h3 className="text-base font-bold text-slate-800">Your account is not linked yet</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">Expense claims need a member record.</p>
                </div>
            ) : (
                <div className="space-y-5">
                    <div className="rounded-xl border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-500">
                        <strong className="font-semibold text-slate-700">Money claims only.</strong> Wrong meal counts belong in the
                        {' '}<Link href={route('claims.index')} className="font-semibold text-[var(--accent)] hover:underline">Meal Claims</Link>{' '}
                        module.
                    </div>

                    <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-100 px-6 py-4">
                            <h3 className="text-base font-bold text-slate-900">Your expense claims</h3>
                        </div>
                        {rows.length > 0 ? (
                            <ul className="divide-y divide-slate-100">
                                {rows.map((claim) => (
                                    <li key={claim.id} className="flex items-start justify-between gap-3 px-6 py-4">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-semibold text-slate-900">{claim.title}</span>
                                                <span className="text-[11px] font-medium text-slate-400">{claim.kind_label}{claim.subject_label ? ` · ${claim.subject_label}` : ''}</span>
                                            </div>
                                            {claim.description && <p className="mt-1 text-xs text-slate-500">{claim.description}</p>}
                                            <div className="mt-1 flex flex-wrap gap-x-4 text-[11px] text-slate-400">
                                                <span>Submitted {claim.created_at}</span>
                                                {claim.amount !== null && <span className="font-semibold text-slate-600">{money(claim.amount, false)}{claim.credit_to_balance ? ' → balance' : ''}</span>}
                                            </div>
                                            {claim.review_notes && (
                                                <p className="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"><strong className="font-semibold">Manager note:</strong> {claim.review_notes}</p>
                                            )}
                                        </div>
                                        <StatusChip status={claim.status} label={claim.status_label} />
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="py-14 text-center">
                                <p className="text-sm font-semibold text-slate-600">No expense claims yet.</p>
                                <p className="mt-1 text-xs text-slate-400">Bought something for the mess? Claim it back here.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            <Modal
                open={open}
                onClose={close}
                title="New expense claim"
                description="Your manager reviews this; nothing changes on your balance until it is approved."
                maxWidth="max-w-xl"
                footer={
                    <>
                        <button type="button" onClick={close} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="expense-claim-form" disabled={processing}
                            className="rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Submitting...' : 'Submit claim'}
                        </button>
                    </>
                }
            >
                <form id="expense-claim-form" onSubmit={submit} className="space-y-4">
                    <Field label="What is this about?" name="kind" type="select" required value={data.kind} error={errors.kind}
                        options={(kinds || []).map((k) => ({ value: k.value, label: k.label }))}
                        onChange={(e) => setData('kind', e.target.value)} />

                    {data.kind === 'dispute' && (
                        <Field label="Missing what?" name="subject" type="select" required value={data.subject} error={errors.subject}
                            options={(subjects || []).filter((s) => s.value !== 'meal').map((s) => ({ value: s.value, label: s.label }))}
                            onChange={(e) => setData('subject', e.target.value)} />
                    )}

                    <Field label={isExpense ? 'What did you buy?' : 'Summary'} name="title" required value={data.title} error={errors.title}
                        placeholder={isExpense ? 'e.g. Bought rice and oil for the kitchen' : 'e.g. My deposit on 5 Sep is missing'}
                        onChange={(e) => setData('title', e.target.value)} />

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label={isExpense ? 'Amount you spent' : 'Amount missing'} name="amount" type="number" step="0.01" min="0.01" required
                            value={data.amount} error={errors.amount} onChange={(e) => setData('amount', e.target.value)} />
                        <Field label="Payment method" name="payment_method" type="select" value={data.payment_method} error={errors.payment_method}
                            options={(paymentMethods || []).map((m) => ({ value: m, label: m }))}
                            onChange={(e) => setData('payment_method', e.target.value)} />
                    </div>

                    {isExpense && (
                        <Field label="Credit this amount to my money-in balance on approval?" name="credit_to_balance" type="select"
                            value={data.credit_to_balance ? '1' : '0'} error={errors.credit_to_balance}
                            options={[{ value: '1', label: 'Yes - credit my balance when approved' }, { value: '0', label: 'No - handle it another way' }]}
                            onChange={(e) => setData('credit_to_balance', e.target.value === '1')} />
                    )}

                    <Field label="Date it happened" name="claim_date" type="date" value={data.claim_date} error={errors.claim_date}
                        onChange={(e) => setData('claim_date', e.target.value)} />
                    <Field label="Details" name="description" type="textarea" value={data.description} error={errors.description}
                        placeholder="Where you bought it, receipt reference..." onChange={(e) => setData('description', e.target.value)} />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

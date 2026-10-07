import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import useMoney from '@/Utils/useMoney';
import useTerminology from '@/Utils/useTerminology';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

/**
 * A member's own deposit history, plus the "Make Payment" submission flow.
 *
 * A submitted payment is PENDING until a manager approves it, so it is shown
 * separately and never counted in the deposited total until approved.
 */
const EMPTY_PAYMENT = {
    amount: '',
    payment_method: 'Online Gateway',
    reference: '',
    notes: '',
};

function StatusChip({ status, label }) {
    const tone = {
        pending: 'border-amber-200 bg-amber-50 text-amber-700',
        rejected: 'border-rose-200 bg-rose-50 text-rose-600',
        approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    }[status] || 'border-slate-200 bg-slate-100 text-slate-500';

    return <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${tone}`}>{label}</span>;
}

export default function Deposits({ hasMemberRecord = true, member = {}, deposits, totalDeposited = 0, pendingCount = 0, paymentMethods = [] }) {
    const money = useMoney();
    const { t } = useTerminology();
    const { flash } = usePage().props;

    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ ...EMPTY_PAYMENT });

    if (!hasMemberRecord) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-bold text-slate-900">My {t('deposits', 'Deposits')}</h2>}>
                <Head title="My Deposits" />
                <div className="rounded-2xl border-slate-200 bg-white p-10 text-center shadow-sm">
                    <h3 className="text-base font-bold text-slate-800">No member record linked</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Your login is not yet linked to a member record. Ask your manager to link it.
                    </p>
                </div>
            </AuthenticatedLayout>
        );
    }

    const rows = deposits?.data || [];

    const openModal = () => { clearErrors(); reset(); setData({ ...EMPTY_PAYMENT }); setOpen(true); };
    const closeModal = () => { setOpen(false); reset(); };
    const submit = (e) => { e.preventDefault(); post(route('member.deposits.store'), { preserveScroll: true, onSuccess: () => closeModal() }); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">My {t('deposits', 'Deposits')}</h2>
                        <p className="mt-0.5 text-xs font-medium text-slate-500">Every payment recorded against your account</p>
                    </div>
                    <button type="button" onClick={openModal}
                        className="inline-flex items-center gap-2 self-start rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Make Payment
                    </button>
                </div>
            }
        >
            <Head title="My Deposits" />

            {flash?.success && (
                <div role="status" className="mb-4 rounded-lg border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{flash.success}</div>
            )}
            {flash?.error && (
                <div role="alert" className="mb-4 rounded-lg border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-700">{flash.error}</div>
            )}

            <div className="space-y-5">
                {/* Lifetime total (approved only) */}
                <div className="rounded-2xl border-emerald-200 bg-emerald-50/60 p-6 shadow-sm">
                    <p className="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Deposited</p>
                    <p className="mt-1 text-4xl font-extrabold tracking-tight text-emerald-600">{money(totalDeposited, false)}</p>
                    <p className="mt-1 text-xs font-medium text-slate-500">
                        Excludes any reversed entries and payments still awaiting approval.
                    </p>
                </div>

                {pendingCount > 0 && (
                    <div className="flex items-center gap-2 rounded-lg border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
                        <span className="font-bold">{pendingCount}</span>
                        payment{pendingCount === 1 ? '' : 's'} awaiting manager approval.
                    </div>
                )}

                <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-6 py-4">
                        <h3 className="text-base font-bold text-slate-900">Deposit History</h3>
                        <p className="text-xs text-slate-500">Most recent first</p>
                    </div>

                    {rows.length > 0 ? (
                        <ul className="divide-y divide-slate-100">
                            {rows.map((dep) => (
                                <li key={dep.id} className={`flex items-center justify-between gap-3 px-6 py-4 ${dep.reversed ? 'opacity-60' : ''}`}>
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className={`text-base font-bold ${dep.reversed ? 'text-slate-400 line-through' : 'text-slate-800'}`}>
                                                {money(dep.amount, false)}
                                            </span>
                                            {dep.status && dep.status !== 'approved' && <StatusChip status={dep.status} label={dep.status_label} />}
                                        </div>
                                        <div className="truncate text-xs text-slate-400">
                                            {dep.method || 'Cash'}
                                            {dep.kind && dep.kind !== 'personal' ? ` · ${dep.kind}` : ''}
                                            {dep.reference ? ` · Ref ${dep.reference}` : ''}
                                            {dep.notes ? ` · ${dep.notes}` : ''}
                                        </div>
                                    </div>
                                    <div className="flex-shrink-0 text-right">
                                        <div className="text-xs text-slate-400">{dep.date}</div>
                                        {dep.reversed && <span className="text-[10px] font-bold uppercase text-rose-500">Reversed</span>}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="px-6 py-14 text-center text-sm italic text-slate-400">No deposits recorded yet.</p>
                    )}

                    {deposits?.links?.length > 3 && (
                        <div className="flex items-center justify-between gap-4 border-t border-slate-100 px-6 py-4">
                            <p className="text-xs text-slate-500">
                                Showing <strong>{deposits.from}</strong>–<strong>{deposits.to}</strong> of <strong>{deposits.total}</strong>
                            </p>
                            <div className="flex flex-wrap gap-1">
                                {deposits.links.map((link, index) => (
                                    <Link key={index} href={link.url || '#'} preserveScroll
                                        className={`rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors ${link.active ? 'border-[var(--accent)] bg-[var(--accent)] text-white' : link.url ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'pointer-events-none border-slate-100 bg-white text-slate-300'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Modal
                open={open}
                onClose={closeModal}
                title="Make a payment"
                description="Tell your manager about a payment you made. It is credited once approved."
                maxWidth="max-w-lg"
                footer={
                    <>
                        <button type="button" onClick={closeModal} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="member-payment-form" disabled={processing}
                            className="rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Submitting...' : 'Submit payment'}
                        </button>
                    </>
                }
            >
                <form id="member-payment-form" onSubmit={submit} className="space-y-4">
                    <Field label="Amount paid" name="amount" type="number" step="0.01" min="0.01" required
                        value={data.amount} error={errors.amount} onChange={(e) => setData('amount', e.target.value)} />

                    <Field label="Payment method" name="payment_method" type="select" required
                        value={data.payment_method} error={errors.payment_method}
                        options={(paymentMethods?.length ? paymentMethods : ['Online Gateway', 'Bank Transfer', 'Mobile Banking', 'Cash']).map((m) => ({ value: m, label: m }))}
                        onChange={(e) => setData('payment_method', e.target.value)} />

                    <Field label="Transaction reference" name="reference" value={data.reference} error={errors.reference}
                        placeholder="e.g. bKash TrxID, bank slip no." onChange={(e) => setData('reference', e.target.value)} />

                    <Field label="Note to your manager" name="notes" type="textarea" value={data.notes} error={errors.notes}
                        placeholder="Anything they should know about this payment..." onChange={(e) => setData('notes', e.target.value)} />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

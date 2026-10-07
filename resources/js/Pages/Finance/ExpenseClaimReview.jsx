import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import useMoney from '@/Utils/useMoney';
import useTerminology from '@/Utils/useTerminology';
import { useFeedback } from '@/Components/Feedback/FeedbackProvider';
import { Head, Link, router } from '@inertiajs/react';

/**
 * EXPENSE CLAIM REVIEW (the FINANCE module - manager side).
 *
 * Financial claims only (out-of-pocket purchases, missing deposits). Approving
 * applies the money adjustment - crediting the member's balance when they asked
 * for it. Meal-count disputes are reviewed inside the Meal & Schedule module.
 */
function StatusChip({ status, label }) {
    const tone = {
        pending: 'border-amber-100 bg-amber-50 text-amber-700',
        approved: 'border-emerald-100 bg-emerald-50 text-emerald-700',
        rejected: 'border-rose-100 bg-rose-50 text-rose-700',
    }[status] || 'border-slate-200 bg-slate-100 text-slate-500';

    return <span className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${tone}`}>{label}</span>;
}

export default function ExpenseClaimReview({ claims, stats = {}, filters = {} }) {
    const money = useMoney();
    const { t } = useTerminology();
    const { confirm } = useFeedback();

    const rows = claims?.data || [];
    const [decision, setDecision] = useState(null);
    const [notes, setNotes] = useState('');
    const [approvedAmount, setApprovedAmount] = useState('');
    const [processing, setProcessing] = useState(false);

    const openDecision = (claim, mode) => { setDecision({ claim, mode }); setNotes(''); setApprovedAmount(claim.amount !== null ? String(claim.amount) : ''); };
    const closeDecision = () => { setDecision(null); setNotes(''); };

    const applyFilter = (next) => {
        router.get(route('expense-claims.review'), { ...filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const submitDecision = async (e) => {
        e.preventDefault();
        if (!decision) return;

        if (decision.mode === 'approve') {
            const ok = await confirm({
                title: 'Approve this claim?',
                message: 'This applies the money adjustment and updates the member\'s balance if credit was requested.',
                tone: 'accent',
                confirmLabel: 'Approve & apply',
            });
            if (!ok) return;
        }

        setProcessing(true);
        const payload = { review_notes: notes };
        if (decision.mode === 'approve' && approvedAmount !== '') payload.approved_amount = approvedAmount;

        const url = decision.mode === 'approve' ? route('claims.approve', decision.claim.id) : route('claims.reject', decision.claim.id);
        router.patch(url, payload, { preserveScroll: true, onSuccess: () => { closeDecision(); setProcessing(false); }, onFinish: () => setProcessing(false) });
    };

    const tabs = [
        { value: 'pending', label: 'Pending', count: stats.pending ?? 0 },
        { value: 'approved', label: 'Approved', count: stats.approved ?? 0 },
        { value: 'rejected', label: 'Rejected', count: stats.rejected ?? 0 },
        { value: 'all', label: 'All', count: null },
    ];
    const activeStatus = filters?.status || 'pending';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Expense Claims</h2>
                    <p className="mt-0.5 text-xs font-medium text-slate-500">Review the money your {t('members', 'members')} spent out-of-pocket.</p>
                </div>
            }
        >
            <Head title="Expense Claims" />

            <div className="space-y-5">
                <div className="flex flex-wrap items-center gap-2">
                    {tabs.map((tab) => (
                        <button key={tab.value} type="button" onClick={() => applyFilter({ status: tab.value })}
                            className={`inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-xs font-semibold transition-colors ${activeStatus === tab.value ? 'border-[var(--accent)] bg-[var(--accent-soft)] text-[var(--accent)]' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'}`}>
                            {tab.label}
                            {tab.count !== null && <span className="rounded-full bg-white/70 px-1.5 text-[10px] font-bold">{tab.count}</span>}
                        </button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                    {rows.length > 0 ? (
                        <ul className="divide-y divide-slate-100">
                            {rows.map((claim) => (
                                <li key={claim.id} className="flex flex-col gap-3 p-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-bold text-slate-900">{claim.title}</span>
                                            <StatusChip status={claim.status} label={claim.status_label} />
                                            <span className="text-[11px] font-medium text-slate-400">{claim.kind_label}{claim.subject_label ? ` · ${claim.subject_label}` : ''}</span>
                                        </div>
                                        <div className="mt-1 flex flex-wrap gap-x-4 text-xs text-slate-500">
                                            <span className="font-semibold text-slate-700">{claim.student?.name}{claim.student?.roll ? ` (${claim.student.roll})` : ''}</span>
                                            <span>Submitted {claim.created_at}</span>
                                            {claim.kind === 'expense' && <span>{claim.credit_to_balance ? 'Credit to balance' : 'No balance credit'}</span>}
                                        </div>
                                        {claim.description && <p className="mt-2 text-sm text-slate-600">{claim.description}</p>}
                                        {claim.reviewer && (
                                            <p className="mt-2 text-[11px] text-slate-400">Reviewed by <strong className="font-semibold text-slate-600">{claim.reviewer}</strong>{claim.review_notes ? ` - "${claim.review_notes}"` : ''}</p>
                                        )}
                                    </div>
                                    <div className="flex flex-shrink-0 flex-col items-start gap-2 lg:items-end">
                                        {claim.amount !== null && <span className="text-lg font-extrabold text-slate-900">{money(claim.amount, false)}</span>}
                                        {claim.status === 'pending' && (
                                            <div className="flex gap-2">
                                                <button type="button" onClick={() => openDecision(claim, 'approve')} className="rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700">Approve</button>
                                                <button type="button" onClick={() => openDecision(claim, 'reject')} className="rounded-lg border-rose-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50">Reject</button>
                                            </div>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="py-16 text-center">
                            <p className="text-sm font-semibold text-slate-600">Nothing to review here.</p>
                            <p className="mt-1 text-xs text-slate-400">Financial claims appear here as they are submitted.</p>
                        </div>
                    )}
                </div>
            </div>

            <Modal
                open={Boolean(decision)}
                onClose={closeDecision}
                title={decision?.mode === 'approve' ? 'Approve claim' : 'Reject claim'}
                description={decision?.mode === 'approve' ? 'Applies the money adjustment and credits the balance if requested.' : 'Closes the claim with no balance change.'}
                footer={
                    <>
                        <button type="button" onClick={closeDecision} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="fin-decision-form" disabled={processing}
                            className={`rounded-lg px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors disabled:opacity-50 ${decision?.mode === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'}`}>
                            {processing ? 'Saving...' : decision?.mode === 'approve' ? 'Approve & apply' : 'Reject claim'}
                        </button>
                    </>
                }
            >
                {decision && (
                    <form id="fin-decision-form" onSubmit={submitDecision} className="space-y-4">
                        <div className="rounded-lg border-slate-200 bg-slate-50 p-4 text-sm">
                            <div className="font-semibold text-slate-800">{decision.claim.title}</div>
                            <div className="mt-0.5 text-xs text-slate-500">
                                {decision.claim.student?.name} · {decision.claim.kind_label}
                                {decision.claim.amount !== null ? ` · claimed ${money(decision.claim.amount, false)}` : ''}
                            </div>
                        </div>

                        {decision.mode === 'approve' && decision.claim.amount !== null && (
                            <Field label="Amount to apply" name="approved_amount" type="number" step="0.01" min="0.01" value={approvedAmount}
                                hint="Defaults to the claimed amount; change for a partial approval." onChange={(e) => setApprovedAmount(e.target.value)} />
                        )}

                        <Field label={`Note to the ${t('member', 'member')}`} name="review_notes" type="textarea" value={notes}
                            placeholder={decision.mode === 'approve' ? 'Optional note...' : 'Explain the rejection...'} onChange={(e) => setNotes(e.target.value)} />
                    </form>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}

import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import { Head, router, useForm } from '@inertiajs/react';

/**
 * MEMBER MEAL VOTING + SUGGESTIONS.
 *
 * Members vote (one vote per week, changeable) on the options an administrator
 * configured in Settings → Meal Voting, and submit free-text meal suggestions.
 */
export default function Voting({ hasMemberRecord = true, options = [], myVote = null, weekStart = '', suggestions = [] }) {
    const [suggestOpen, setSuggestOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({ title: '', body: '' });

    const totalVotes = options.reduce((sum, o) => sum + (o.votes || 0), 0);

    const castVote = (optionId) => {
        router.post(route('member.voting.vote'), { option_id: optionId }, { preserveScroll: true });
    };

    const submitSuggestion = (e) => {
        e.preventDefault();
        post(route('member.voting.suggest'), { preserveScroll: true, onSuccess: () => { reset(); setSuggestOpen(false); } });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Meal Voting &amp; Suggestions</h2>
                        <p className="mt-0.5 text-xs font-medium text-slate-500">
                            Vote for next week's menu, or suggest a meal. Week of {weekStart}.
                        </p>
                    </div>
                    {hasMemberRecord && (
                        <button type="button" onClick={() => { clearErrors(); setSuggestOpen(true); }}
                            className="inline-flex items-center gap-2 self-start rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                            Suggest a meal
                        </button>
                    )}
                </div>
            }
        >
            <Head title="Meal Voting" />

            {!hasMemberRecord ? (
                <div className="rounded-2xl border-slate-200 bg-white p-10 text-center shadow-sm">
                    <h3 className="text-base font-bold text-slate-800">Your account is not linked yet</h3>
                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">Voting needs a member record. Ask your manager to link your account.</p>
                </div>
            ) : (
                <div className="space-y-5">
                    {/* Voting options - configured by admins */}
                    <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-100 px-6 py-4">
                            <h3 className="text-base font-bold text-slate-900">This week's options</h3>
                            <p className="text-xs text-slate-500">{totalVotes} vote{totalVotes === 1 ? '' : 's'} so far</p>
                        </div>
                        {options.length > 0 ? (
                            <ul className="divide-y divide-slate-100">
                                {options.map((o) => {
                                    const pct = totalVotes > 0 ? Math.round((o.votes / totalVotes) * 100) : 0;
                                    const chosen = myVote === o.id;
                                    return (
                                        <li key={o.id} className="px-6 py-4">
                                            <div className="flex items-center justify-between gap-4">
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-semibold text-slate-900">{o.label}</span>
                                                        {chosen && <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">Your vote</span>}
                                                    </div>
                                                    {o.description && <p className="mt-0.5 text-xs text-slate-500">{o.description}</p>}
                                                    <div className="mt-2 h-1.5 w-48 overflow-hidden rounded-full bg-slate-100">
                                                        <div className="h-full rounded-full bg-[var(--accent)]" style={{ width: `${pct}%` }} />
                                                    </div>
                                                    <p className="mt-1 text-[11px] text-slate-400">{o.votes} vote{o.votes === 1 ? '' : 's'} · {pct}%</p>
                                                </div>
                                                <button type="button" onClick={() => castVote(o.id)} disabled={chosen}
                                                    className={`flex-shrink-0 rounded-lg px-4 py-2 text-xs font-bold transition-colors ${chosen ? 'bg-emerald-600 text-white' : 'bg-[var(--accent)] text-white hover:opacity-90'}`}>
                                                    {chosen ? 'Voted' : 'Vote'}
                                                </button>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <p className="px-6 py-10 text-center text-sm text-slate-400">
                                No voting options have been set up yet.
                            </p>
                        )}
                    </div>

                    {/* My suggestions */}
                    <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-100 px-6 py-4">
                            <h3 className="text-base font-bold text-slate-900">Your suggestions</h3>
                            <p className="text-xs text-slate-500">Ideas you have sent to the manager.</p>
                        </div>
                        {suggestions.length > 0 ? (
                            <ul className="divide-y divide-slate-100">
                                {suggestions.map((s) => (
                                    <li key={s.id} className="px-6 py-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-semibold text-slate-800">{s.title}</span>
                                            <span className={`rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase ${s.status === 'reviewed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>{s.status}</span>
                                        </div>
                                        {s.body && <p className="mt-0.5 text-xs text-slate-500">{s.body}</p>}
                                        <p className="mt-1 text-[11px] text-slate-400">{s.created_at}</p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="px-6 py-10 text-center text-sm text-slate-400">You haven't sent any suggestions yet.</p>
                        )}
                    </div>
                </div>
            )}

            <Modal
                open={suggestOpen}
                onClose={() => setSuggestOpen(false)}
                title="Suggest a meal"
                description="The manager will see this on their voting & suggestions board."
                footer={
                    <>
                        <button type="button" onClick={() => setSuggestOpen(false)} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="suggest-form" disabled={processing}
                            className="rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Sending...' : 'Send suggestion'}
                        </button>
                    </>
                }
            >
                <form id="suggest-form" onSubmit={submitSuggestion} className="space-y-4">
                    <Field label="What meal do you want?" name="title" required value={data.title} error={errors.title}
                        placeholder="e.g. Add khichuri on Fridays" onChange={(e) => setData('title', e.target.value)} />
                    <Field label="Details" name="body" type="textarea" value={data.body} error={errors.body}
                        placeholder="Why it's a good idea, when to serve it..." onChange={(e) => setData('body', e.target.value)} />
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

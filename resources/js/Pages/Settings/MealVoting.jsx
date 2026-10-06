import React, { useState } from 'react';
import SettingsLayout from '@/Layouts/SettingsLayout';
import Modal from '@/Components/UI/Modal';
import Field from '@/Components/UI/Field';
import { useFeedback } from '@/Components/Feedback/FeedbackProvider';
import { Head, router, useForm } from '@inertiajs/react';

/**
 * ADMIN MEAL VOTING OPTIONS.
 *
 * The choices members vote on are explicitly configured here - never hard-coded.
 * Mirrors the SubsidySources settings screen.
 */
const EMPTY = { label: '', description: '', sort: 0 };

export default function MealVoting({ options = [] }) {
    const { confirm } = useFeedback();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({ ...EMPTY });

    const isEditing = Boolean(editing);

    const openCreate = () => {
        clearErrors(); reset(); setData({ ...EMPTY }); setEditing(null); setOpen(true);
    };

    const openEdit = (option) => {
        clearErrors();
        setEditing(option);
        setData({ label: option.label || '', description: option.description || '', sort: option.sort || 0 });
        setOpen(true);
    };

    const close = () => { setOpen(false); setEditing(null); reset(); };

    const submit = (e) => {
        e.preventDefault();
        if (isEditing) {
            put(route('settings.meal-voting.update', editing.id), { preserveScroll: true, onSuccess: () => close() });
        } else {
            post(route('settings.meal-voting.store'), { preserveScroll: true, onSuccess: () => close() });
        }
    };

    const remove = async (option) => {
        const ok = await confirm({
            title: `Delete "${option.label}"?`,
            message: 'Options that already have votes cannot be deleted - deactivate them instead.',
            tone: 'danger',
            confirmLabel: 'Delete option',
        });
        if (!ok) return;
        router.delete(route('settings.meal-voting.destroy', option.id), { preserveScroll: true });
    };

    return (
        <SettingsLayout title="Settings">
            <Head title="Meal Voting" />

            <div className="overflow-hidden rounded-2xl border-slate-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 className="text-base font-bold text-slate-900">Meal voting options</h3>
                        <p className="text-xs text-slate-500">The menu choices members can vote on each week.</p>
                    </div>
                    <button type="button" onClick={openCreate}
                        className="inline-flex items-center gap-2 rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" /></svg>
                        New option
                    </button>
                </div>

                {options.length > 0 ? (
                    <ul className="divide-y divide-slate-100">
                        {options.map((o) => (
                            <li key={o.id} className="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="font-semibold text-slate-900">{o.label}</span>
                                        {!o.is_active && <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500">Inactive</span>}
                                    </div>
                                    {o.description && <p className="mt-0.5 text-xs text-slate-500">{o.description}</p>}
                                    <p className="mt-0.5 text-[11px] text-slate-400">{o.vote_count} vote{o.vote_count === 1 ? '' : 's'} · order {o.sort}</p>
                                </div>
                                <div className="flex flex-shrink-0 gap-3">
                                    <button type="button" onClick={() => openEdit(o)} className="text-xs font-semibold text-[var(--accent)] hover:underline">Edit</button>
                                    <button type="button" onClick={() => remove(o)} className="text-xs font-semibold text-rose-500 hover:underline">Delete</button>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <div className="py-14 text-center">
                        <p className="text-sm font-semibold text-slate-600">No voting options yet.</p>
                        <p className="mt-1 text-xs text-slate-400">Add the choices members should vote on.</p>
                    </div>
                )}
            </div>

            <Modal
                open={open}
                onClose={close}
                title={isEditing ? `Edit "${editing?.label}"` : 'New voting option'}
                description="Members will see this as a choice in their voting screen."
                footer={
                    <>
                        <button type="button" onClick={close} className="rounded-lg border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100">Cancel</button>
                        <button type="submit" form="vote-option-form" disabled={processing}
                            className="rounded-lg bg-[var(--accent)] px-5 py-2 text-sm font-bold text-white shadow-sm transition-opacity hover:opacity-90 disabled:opacity-50">
                            {processing ? 'Saving...' : isEditing ? 'Save changes' : 'Add option'}
                        </button>
                    </>
                }
            >
                <form id="vote-option-form" onSubmit={submit} className="space-y-4">
                    <Field label="Option" name="label" required value={data.label} error={errors.label}
                        placeholder="e.g. Chicken biryani" onChange={(e) => setData('label', e.target.value)} />
                    <Field label="Description" name="description" value={data.description} error={errors.description}
                        placeholder="Optional short description" onChange={(e) => setData('description', e.target.value)} />
                    <Field label="Order" name="sort" type="number" min="0" value={data.sort} error={errors.sort}
                        hint="Lower numbers appear first." onChange={(e) => setData('sort', Number(e.target.value))} />
                </form>
            </Modal>
        </SettingsLayout>
    );
}

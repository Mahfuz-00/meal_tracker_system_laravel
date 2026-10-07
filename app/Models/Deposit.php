<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use BelongsToInstitution;
    use HasFactory;

    protected $fillable = [
        'institution_id', 'student_id', 'amount', 'kind', 'subsidy_id',
        'payment_method', 'recorded_by', 'transaction_id', 'notes',
        'reversed_at', 'reversed_by', 'reversal_transaction_id',
        'status', 'reference', 'submitted_by', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reversed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** personal | subsidy | credit */
    public const KINDS = [
        'personal' => 'Personal deposit',
        'subsidy' => 'Institutional subsidy',
        'credit' => 'Credit adjustment',
    ];

    /** A member-submitted payment starts pending; manager-recorded ones are approved. */
    public const STATUSES = [
        'pending' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    /**
     * Only approved deposits exist as far as money is concerned. A pending or
     * rejected submission must never affect a balance, so it is excluded from
     * every default query; the review queue opts out explicitly.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('approved', function ($query) {
            $query->where(function ($q) {
                $q->where('status', 'approved')->orWhereNull('status');
            });
        });
    }

    /**
     * Route binding must be able to reach a pending deposit (so it can be
     * approved/rejected), bypassing the approved-only global scope.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('approved')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    /** A reversed deposit no longer counts toward any balance. */
    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    /** Only deposits that have not been reversed. */
    public function scopeActive($query)
    {
        return $query->whereNull('reversed_at');
    }

    /** Awaiting manager approval (bypasses the approved-only global scope). */
    public function scopePending($query)
    {
        return $query->withoutGlobalScope('approved')->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return ($this->status ?? 'approved') === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status ?? 'approved'] ?? 'Approved';
    }

    /** The member (user) who submitted this payment, when it came from the portal. */
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** The manager who approved/rejected this payment. */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeReversed($query)
    {
        return $query->whereNotNull('reversed_at');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /** The administrator who reversed this deposit. */
    public function reverser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reversal_transaction_id');
    }

    public function subsidy()
    {
        return $this->belongsTo(Subsidy::class);
    }

    /**
     * Only genuine personal deposits count toward a member's own funds. Subsidy
     * money is tracked separately so balance rules stay strict.
     */
    public function scopePersonal($query)
    {
        return $query->where('kind', 'personal');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

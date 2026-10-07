<?php

namespace Tests\Browser\Payments;

use App\Models\Deposit;
use App\Models\Student;
use App\Models\Transaction;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * DEPOSIT MODULE - member payment submission + manager approval workflow.
 *
 * A member submits a payment (online gateway / bank / mobile banking); it stays
 * PENDING and never touches the balance until an admin or meal manager approves
 * it. Rejection leaves the balance untouched.
 */
class PaymentRefundTest extends DuskTestCase
{
    use DuskSupport;

    public function test_member_can_submit_a_payment_for_approval(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution, ['name' => 'Tanvir Ahmed']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->step('Member', 'Deposits', 'submit a payment', __LINE__);

        $this->httpAs($member)->post('/my/deposits', [
            'amount' => 1500,
            'payment_method' => 'Mobile Banking',
            'reference' => 'TRX12345',
            'notes' => 'Paid via bKash',
        ])->assertSessionHas('success');

        $deposit = Deposit::withoutGlobalScope('approved')
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertSame('pending', $deposit->status);

        // A pending payment must NOT count toward the balance yet (the model's
        // global scope hides it from every money query).
        $this->assertSame(0, Deposit::where('student_id', $student->id)->count());

        // The member sees it flagged as awaiting approval.
        $this->browse(function (Browser $browser) use ($member) {
            $this->loginViaForm($browser, $member);
            $browser->visit('/my/deposits')
                ->waitForText('Make Payment', 20)
                ->assertSee('awaiting manager approval');
        });
    }

    public function test_manager_approval_credits_the_member_balance(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'deposit-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Sadia Islam']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->httpAs($member)->post('/my/deposits', [
            'amount' => 2000,
            'payment_method' => 'Online Gateway',
            'reference' => 'GW-99',
        ])->assertSessionHas('success');

        $deposit = Deposit::withoutGlobalScope('approved')
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->step('InstitutionAdmin', 'Deposits', 'approve -> balance credited', __LINE__);

        $this->httpAs($admin)->patch("/meals/deposits/{$deposit->id}/approve", [])
            ->assertSessionHas('success');

        $deposit->refresh();

        $this->assertSame('approved', $deposit->status);
        $this->assertNotNull($deposit->transaction_id, 'Approval should post the cash-in transaction.');

        // It now counts toward the member's balance.
        $this->assertSame(1, Deposit::where('student_id', $student->id)->count());
        $this->assertSame(
            1,
            Transaction::where('student_id', $student->id)->where('type', 'in')->count()
        );
    }

    public function test_manager_can_reject_a_payment(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'reject-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Mahin Chowdhury']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->httpAs($member)->post('/my/deposits', [
            'amount' => 900,
            'payment_method' => 'Bank Transfer',
            'reference' => 'SLIP-7',
        ])->assertSessionHas('success');

        $deposit = Deposit::withoutGlobalScope('approved')
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->httpAs($admin)->patch("/meals/deposits/{$deposit->id}/reject", [
            'review_notes' => 'Could not verify the transaction.',
        ])->assertSessionHas('success');

        $this->assertSame('rejected', $deposit->fresh()->status);

        // A rejected payment is never credited.
        $this->assertSame(0, Deposit::where('student_id', $student->id)->count());
    }
}

<?php

namespace Tests\Browser\Finance;

use App\Models\Claim;
use App\Models\Deposit;
use App\Models\Student;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * OUT-OF-POCKET EXPENSE CLAIMS (the FINANCE module).
 *
 * Financial claims are DECOUPLED from the Meal module, and approving one can
 * adjust the member's money-in balance.
 */
class MemberClaimAdjustmentTest extends DuskTestCase
{
    use DuskSupport;

    public function test_member_expense_claim_lives_in_the_finance_module(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution, ['name' => 'Shafiq Rahman']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->step('Member', 'Finance', 'submit an out-of-pocket expense claim', __LINE__);

        $this->httpAs($member)->post('/claims', [
            'kind' => 'expense',
            'amount' => 750,
            'credit_to_balance' => true,
            'title' => 'Bought cooking gas for the kitchen',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('expense', $claim->kind);

        // It shows in the FINANCE module's member page.
        $this->browse(function (Browser $browser) use ($member) {
            $this->loginViaForm($browser, $member);
            $browser->visit('/my/expense-claims')
                ->waitForText('Expense Claims', 20)
                ->assertSee('Bought cooking gas for the kitchen');
        });
    }

    public function test_approving_a_credited_expense_adjusts_member_balance(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'finance-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Farhana Yasmin']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->httpAs($member)->post('/claims', [
            'kind' => 'expense',
            'amount' => 500,
            'credit_to_balance' => true,
            'title' => 'Bought rice and oil',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();

        $this->step('InstitutionAdmin', 'Finance', 'approve -> balance adjusted', __LINE__);

        $this->httpAs($admin)->patch("/claims/{$claim->id}/approve", [])
            ->assertSessionHas('success');

        $this->assertSame('approved', $claim->fresh()->status);

        // The member's money-in balance was credited (a deposit was created).
        $this->assertSame(
            1,
            Deposit::where('student_id', $student->id)->count(),
            'Approving a credited expense claim should adjust the member balance.'
        );
    }
}

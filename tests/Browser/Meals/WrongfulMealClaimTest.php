<?php

namespace Tests\Browser\Meals;

use App\Models\Claim;
use App\Models\Deposit;
use App\Models\MealEntry;
use App\Models\Student;
use App\Models\Transaction;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * WRONGFUL MEAL COUNT + MONETARY CLAIMS.
 *
 *   - A member can report meals MISSED but not counted (added back), OR meals
 *     counted WRONGLY while off/absent (removed).
 *   - Monetary claims (out-of-pocket purchases) are SEPARATE, and the member
 *     chooses whether the approved amount is credited to their money-in balance.
 *
 * Backed by the Claims module (kind=dispute subject=meal, and kind=expense).
 */
class WrongfulMealClaimTest extends DuskTestCase
{
    use DuskSupport;

    public function test_member_claims_missing_meals_and_admin_adds_them_back(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'claim-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Tanvir Ahmed']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $date = now()->toDateString();

        // The date must ALREADY have a recorded meal entry (conditional rule).
        // Lunch was recorded; breakfast was missed.
        MealEntry::create([
            'institution_id' => $institution->id,
            'student_id' => $student->id,
            'date' => $date,
            'breakfast' => 0,
            'lunch' => 1,
            'dinner' => 0,
        ]);

        $this->step('Member', 'Wrongful Meal Claim', 'report missed meals', __LINE__);

        $this->httpAs($member)->post('/claims', [
            'kind' => 'dispute',
            'subject' => 'meal',
            'meal_direction' => 'add',
            'entry_date' => $date,
            'breakfast' => 1,
            'lunch' => 0,
            'dinner' => 0,
            'title' => 'My meals were not counted',
            'description' => 'Breakfast that day was missed.',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('pending', $claim->status);
        $this->assertSame('meal', $claim->subject);
        $this->assertSame('add', $claim->meal_direction);

        $this->step('InstitutionAdmin', 'Wrongful Meal Claim', 'approve -> meals added back', __LINE__);

        $this->httpAs($admin)->patch("/claims/{$claim->id}/approve", [])->assertSessionHas('success');

        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertNotNull($claim->reviewed_at);

        $this->assertDatabaseHas('meal_entries', [
            'student_id' => $student->id,
            'breakfast' => 1,
            'lunch' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($member) {
            $this->loginViaForm($browser, $member);
            $browser->visit('/claims')
                ->waitForText('Meal Claims', 20)
                ->assertSee('My meals were not counted');
        });
    }

    public function test_meal_claim_is_rejected_for_a_date_with_no_recorded_meal(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution, ['name' => 'Nadia Sultana']);
        Student::where('user_id', $member->id)->firstOrFail();

        // No MealEntry exists for this date.
        $date = now()->toDateString();

        $this->step('Member', 'Wrongful Meal Claim', 'blocked: no recorded meal', __LINE__);

        $this->httpAs($member)->post('/claims', [
            'kind' => 'dispute',
            'subject' => 'meal',
            'meal_direction' => 'add',
            'entry_date' => $date,
            'breakfast' => 1,
            'title' => 'Should be blocked',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('claims', 0);
    }

    public function test_member_reports_extra_meals_and_admin_removes_them(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'overcount-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Rumana Akter']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $date = now()->toDateString();

        // The member was charged breakfast + lunch while off.
        MealEntry::create([
            'institution_id' => $institution->id,
            'student_id' => $student->id,
            'date' => $date,
            'breakfast' => 1,
            'lunch' => 1,
            'dinner' => 0,
        ]);

        $this->step('Member', 'Wrongful Meal Claim', 'report wrongly counted meals', __LINE__);

        $this->httpAs($member)->post('/claims', [
            'kind' => 'dispute',
            'subject' => 'meal',
            'meal_direction' => 'remove',
            'entry_date' => $date,
            'breakfast' => 1,
            'lunch' => 0,
            'dinner' => 0,
            'title' => 'Breakfast counted while I was away',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('remove', $claim->meal_direction);

        $this->httpAs($admin)->patch("/claims/{$claim->id}/approve", [])->assertSessionHas('success');

        // Breakfast is subtracted back off; lunch is untouched.
        $this->assertDatabaseHas('meal_entries', [
            'student_id' => $student->id,
            'breakfast' => 0,
            'lunch' => 1,
        ]);
    }

    public function test_monetary_claim_credit_flag_controls_balance_credit(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'money-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Jahid Hasan']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        // (1) A purchase with credit-to-balance ON -> approving credits the member.
        $this->httpAs($member)->post('/claims', [
            'kind' => 'expense',
            'amount' => 500,
            'credit_to_balance' => true,
            'title' => 'Bought rice for the kitchen',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();
        $this->assertTrue((bool) $claim->credit_to_balance);

        $this->httpAs($admin)->patch("/claims/{$claim->id}/approve", [])->assertSessionHas('success');

        $this->assertSame(1, Deposit::where('student_id', $student->id)->count(), 'Approving a credit-on claim should credit the balance.');
        $this->assertSame(1, Transaction::where('student_id', $student->id)->where('source', 'claim')->count());

        // (2) A purchase with credit-to-balance OFF -> expense recorded, no credit.
        $this->httpAs($member)->post('/claims', [
            'kind' => 'expense',
            'amount' => 300,
            'credit_to_balance' => false,
            'title' => 'Paid a vendor directly',
        ])->assertSessionHas('success');

        $second = Claim::where('student_id', $student->id)->where('credit_to_balance', false)->firstOrFail();

        $this->httpAs($admin)->patch("/claims/{$second->id}/approve", [])->assertSessionHas('success');

        // Still only ONE deposit (the first claim); the second created a ledger
        // expense but did NOT credit the member's money-in balance.
        $this->assertSame(1, Deposit::where('student_id', $student->id)->count());
        $this->assertSame(2, Transaction::where('student_id', $student->id)->where('source', 'claim')->count());
    }
}

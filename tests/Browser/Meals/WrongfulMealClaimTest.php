<?php

namespace Tests\Browser\Meals;

use App\Models\Claim;
use App\Models\Student;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * WRONGFUL MEAL COUNT CLAIM.
 *
 * A member reports meals that were wrongfully counted / missed on a specific
 * date; a manager reviews it and, on approval, the missed meals are added back
 * to that day's record. Resolution is tracked on the claim.
 *
 * The workflow itself is the existing Claims module (kind=dispute, subject=meal);
 * this test locks in the member-submit -> manager-resolve path for meals.
 */
class WrongfulMealClaimTest extends DuskTestCase
{
    use DuskSupport;

    public function test_member_claims_missing_meals_and_admin_resolves_it(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'claim-admin@example.test']);
        $member = $this->makeMember($institution, ['name' => 'Tanvir Ahmed']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $date = now()->toDateString();

        $this->step('Member', 'Wrongful Meal Claim', 'submit the claim', __LINE__);

        $this->httpAs($member)->post('/claims', [
            'kind' => 'dispute',
            'subject' => 'meal',
            'entry_date' => $date,
            'breakfast' => 1,
            'lunch' => 1,
            'dinner' => 0,
            'title' => 'My meals were not counted',
            'description' => 'I ate breakfast and lunch that day.',
        ])->assertSessionHas('success');

        $claim = Claim::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('pending', $claim->status);
        $this->assertSame('meal', $claim->subject);

        $this->step('InstitutionAdmin', 'Wrongful Meal Claim', 'approve -> meals added back', __LINE__);

        $this->httpAs($admin)->patch("/claims/{$claim->id}/approve", [])
            ->assertSessionHas('success');

        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertNotNull($claim->reviewed_at);

        // The missed meals were added to that day's meal entry.
        $this->assertDatabaseHas('meal_entries', [
            'student_id' => $student->id,
            'breakfast' => 1,
            'lunch' => 1,
        ]);

        // The member sees the resolved claim on their page.
        $this->browse(function (Browser $browser) use ($member) {
            $this->loginViaForm($browser, $member);
            $browser->visit('/claims')
                ->waitForText('My Claims', 20)
                ->assertSee('My meals were not counted');
        });
    }
}

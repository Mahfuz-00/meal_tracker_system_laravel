<?php

namespace Tests\Browser\Meals;

use App\Models\Institution;
use App\Models\MealVoteOption;
use App\Models\Student;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * MEAL VOTING + MEMBER SUGGESTIONS.
 *
 * Admins configure the voting options; members vote and submit suggestions; the
 * manager sees live tallies and every suggestion.
 */
class MealVotingAndSuggestionsTest extends DuskTestCase
{
    use DuskSupport;

    public function test_admin_configures_options_and_member_votes_and_suggests(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $admin = $this->makeInstitutionAdmin($institution, ['email' => 'voting-admin@example.test']);
        $manager = $this->makeMealManager($institution);
        $member = $this->makeMember($institution, ['name' => 'Nusrat Jahan']);
        $student = Student::where('user_id', $member->id)->firstOrFail();
        $student->update(['manager_id' => $manager->id]);

        $this->step('InstitutionAdmin', 'Meal Voting', 'configure a voting option', __LINE__);

        // Admin configures the option (never hard-coded).
        $this->httpAs($admin)->post('/settings/meal-voting', [
            'label' => 'Chicken Biryani',
            'description' => 'Friday special',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('meal_vote_options', ['label' => 'Chicken Biryani']);

        $option = MealVoteOption::where('label', 'Chicken Biryani')->firstOrFail();

        $this->step('Member', 'Meal Voting', 'vote + suggest', __LINE__);

        // Member votes for the admin-configured option.
        $this->httpAs($member)->post('/my/voting/vote', ['option_id' => $option->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('meal_votes', [
            'student_id' => $student->id,
            'option_id' => $option->id,
        ]);

        // Member submits a suggestion.
        $this->httpAs($member)->post('/my/voting/suggest', [
            'title' => 'Add khichuri on Fridays',
            'body' => 'Light and popular.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('meal_suggestions', [
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        // The manager sees the tally and the suggestion.
        $this->browse(function (Browser $browser) use ($manager) {
            $this->loginViaForm($browser, $manager);
            $browser->visit('/meals/voting')
                ->waitForText('Vote tallies', 20)
                ->assertSee('Chicken Biryani')
                ->assertSee('Add khichuri on Fridays');
        });
    }

    public function test_member_cannot_configure_voting_options(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution);

        // A member has no meals.voting.manage permission.
        $this->httpAs($member)->post('/settings/meal-voting', ['label' => 'Hack'])
            ->assertForbidden();
    }
}

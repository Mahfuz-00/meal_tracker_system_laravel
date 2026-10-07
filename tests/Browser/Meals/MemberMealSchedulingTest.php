<?php

namespace Tests\Browser\Meals;

use App\Models\MealSchedule;
use App\Models\Student;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskSupport;
use Tests\DuskTestCase;

/**
 * MEMBER MEAL SCHEDULING (off/on notifications).
 *
 * A member notifies the manager whether they will / won't take meals on a date
 * or range; the manager sees it on the Meal Schedules board.
 */
class MemberMealSchedulingTest extends DuskTestCase
{
    use DuskSupport;

    public function test_member_can_submit_a_meal_schedule(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution, ['name' => 'Samiul Islam']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->step('Member', 'Meal Schedule', 'submit an off request', __LINE__);

        // Submit through the real member endpoint.
        $this->httpAs($member)->post('/my/schedule', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'recurrence' => 'one_time',
            'taking_meals' => false,
            'breakfast' => true,
            'lunch' => true,
            'dinner' => false,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('meal_schedules', [
            'student_id' => $student->id,
            'taking_meals' => 0,
            'status' => 'active',
        ]);

        // The member sees it on their own schedule page.
        $this->browse(function (Browser $browser) use ($member) {
            $this->loginViaForm($browser, $member);
            $browser->visit('/my/schedule')
                ->waitForText('Your schedules', 20)
                ->assertSee('Will skip');
        });
    }

    public function test_manager_sees_member_schedules_on_the_board(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $manager = $this->makeMealManager($institution);
        $member = $this->makeMember($institution, ['name' => 'Rafiul Karim']);
        $student = Student::where('user_id', $member->id)->firstOrFail();
        $student->update(['manager_id' => $manager->id]);

        MealSchedule::create([
            'institution_id' => $institution->id,
            'student_id' => $student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'recurrence' => 'one_time',
            'taking_meals' => false,
            'breakfast' => true,
            'lunch' => true,
            'dinner' => true,
            'status' => 'active',
            'created_by' => $member->id,
        ]);

        $this->step('MealManager', 'Meal Schedules', 'see the schedule board', __LINE__);

        $this->browse(function (Browser $browser) use ($manager, $student) {
            $this->loginViaForm($browser, $manager);
            $browser->visit('/meals/schedules')
                ->waitForText('Skipping meals', 20)
                ->assertSee($student->name);
        });
    }

    public function test_a_schedule_for_a_past_date_is_rejected(): void
    {
        $this->seedRbac();
        $institution = $this->makeInstitution();
        $member = $this->makeMember($institution, ['name' => 'Nusrat Jahan']);
        $student = Student::where('user_id', $member->id)->firstOrFail();

        $this->step('Member', 'Meal Schedule', 'try to schedule a past date', __LINE__);

        // Yesterday is gone - a member may only speak for today and the future.
        $this->httpAs($member)->post('/my/schedule', [
            'start_date' => now()->subDay()->toDateString(),
            'recurrence' => 'one_time',
            'taking_meals' => false,
            'breakfast' => true,
            'lunch' => true,
            'dinner' => true,
        ])->assertSessionHasErrors('start_date');

        $this->assertDatabaseMissing('meal_schedules', [
            'student_id' => $student->id,
        ]);
    }
}

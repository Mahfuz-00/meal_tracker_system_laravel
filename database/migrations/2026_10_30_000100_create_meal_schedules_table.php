<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member meal SCHEDULING / off-on notifications.
 *
 * A member tells the meal manager whether they WILL or WON'T take meals on a
 * date or date range, optionally repeating (daily / weekly / every N days) so a
 * "I never eat breakfast on Sundays" rule can be set once. The manager's board
 * reads these rows to know who to cook for.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Defensive: a database provisioned from a different branch may already
        // carry a table of this name. Never fail (or clobber) an existing table.
        if (Schema::hasTable('meal_schedules')) {
            return;
        }

        Schema::create('meal_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            // The window the request covers (a single day has start == end).
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // one_time | daily | weekly | interval (every `interval_days` days)
            $table->string('recurrence', 20)->default('one_time');
            $table->unsignedSmallInteger('interval_days')->nullable();

            // true = will take the selected meals, false = will SKIP them.
            $table->boolean('taking_meals')->default(false);

            // Which meals the rule applies to.
            $table->boolean('breakfast')->default(true);
            $table->boolean('lunch')->default(true);
            $table->boolean('dinner')->default(true);

            $table->string('note', 255)->nullable();

            // active | cancelled
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['institution_id', 'start_date']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_schedules');
    }
};

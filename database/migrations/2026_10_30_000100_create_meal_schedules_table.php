<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member meal SCHEDULING / off-on notifications.
 *
 * This migration is SELF-HEALING and NON-DESTRUCTIVE, because a database
 * provisioned from a different branch may already hold a `meal_schedules` table
 * with an INCOMPATIBLE shape (the older schema used `starts_on` / `weekdays` /
 * `manager_status` and had no `start_date`). Previously we skipped such a table,
 * which then failed at runtime with "no such column: start_date".
 *
 * Now:
 *   - absent            -> create the table with our full schema,
 *   - ours (start_date) -> nothing to do,
 *   - foreign/older     -> ADD the columns we need (existing rows are preserved;
 *                          we never drop or rename anything).
 *
 * The one NOT NULL column the old shape enforces (`starts_on`) is satisfied by
 * the model, which mirrors `start_date` into it when present (see MealSchedule).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Already our schema.
        if (Schema::hasTable('meal_schedules') && Schema::hasColumn('meal_schedules', 'start_date')) {
            return;
        }

        // Fresh install: create the intended table.
        if (! Schema::hasTable('meal_schedules')) {
            $this->createTable();

            return;
        }

        // Existing, incompatible table: reconcile by ADDING only what is missing.
        Schema::table('meal_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('meal_schedules', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (! Schema::hasColumn('meal_schedules', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (! Schema::hasColumn('meal_schedules', 'taking_meals')) {
                $table->boolean('taking_meals')->default(false);
            }
            if (! Schema::hasColumn('meal_schedules', 'interval_days')) {
                $table->unsignedSmallInteger('interval_days')->nullable();
            }
            if (! Schema::hasColumn('meal_schedules', 'note')) {
                $table->string('note', 255)->nullable();
            }
            // Plain column (no FK): SQLite cannot attach a foreign key to an
            // existing table via ALTER TABLE.
            if (! Schema::hasColumn('meal_schedules', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_schedules');
    }

    /** The intended, full schema. */
    protected function createTable(): void
    {
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
};

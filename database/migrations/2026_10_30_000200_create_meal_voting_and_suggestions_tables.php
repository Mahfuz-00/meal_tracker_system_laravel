<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MEAL VOTING + MEMBER SUGGESTIONS.
 *
 *   meal_vote_options : the choices an ADMIN configures in Settings (e.g. the
 *                       candidate menus for next week). Members can only pick
 *                       from these - the option list is not hard-coded.
 *   meal_votes        : one vote per member per week; voting again replaces the
 *                       member's previous choice for that week.
 *   meal_suggestions  : free-text meal ideas members submit for the manager.
 *
 * Each table is created ONLY if absent, so a database provisioned from another
 * branch can never make this migration fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meal_vote_options')) {
            Schema::create('meal_vote_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->string('label', 120);
                $table->string('description', 500)->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['institution_id', 'label']);
            });
        }

        if (! Schema::hasTable('meal_votes')) {
            Schema::create('meal_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('option_id')->constrained('meal_vote_options')->cascadeOnDelete();
                // The Monday that starts the voting week - one vote per member/week.
                $table->date('week_start');
                $table->timestamps();

                $table->unique(['student_id', 'week_start']);
                $table->index(['institution_id', 'week_start']);
            });
        }

        if (! Schema::hasTable('meal_suggestions')) {
            Schema::create('meal_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('title', 200);
                $table->text('body')->nullable();
                // pending | reviewed
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['institution_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_suggestions');
        Schema::dropIfExists('meal_votes');
        Schema::dropIfExists('meal_vote_options');
    }
};

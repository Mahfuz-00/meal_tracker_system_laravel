<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redefined WRONGFUL MEAL claim + MONETARY claim fields.
 *
 *   meal_direction     : 'add'    - meals were MISSED but not counted (add back)
 *                        'remove' - meals were counted WRONGLY while the member
 *                                   was off/absent (subtract them back off)
 *   credit_to_balance  : for a MONETARY claim, whether the approved amount
 *                        should be credited to the member's money-in balance
 *                        (default true). When false the expense is recorded but
 *                        the member's balance is not credited.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('claims')) {
            return;
        }

        Schema::table('claims', function (Blueprint $table) {
            if (! Schema::hasColumn('claims', 'meal_direction')) {
                $table->string('meal_direction', 10)->nullable();
            }

            if (! Schema::hasColumn('claims', 'credit_to_balance')) {
                $table->boolean('credit_to_balance')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('claims')) {
            return;
        }

        Schema::table('claims', function (Blueprint $table) {
            foreach (['meal_direction', 'credit_to_balance'] as $column) {
                if (Schema::hasColumn('claims', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

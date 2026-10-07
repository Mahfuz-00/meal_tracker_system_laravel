<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MEMBER DEPOSIT SUBMISSIONS.
 *
 * A member can submit a payment ("Made a payment") which stays PENDING until an
 * admin / meal manager approves it. Only approved, non-reversed deposits count
 * toward a member's balance (enforced by the model's global scope), so a pending
 * or rejected submission never moves money.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deposits')) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('deposits', 'status')) {
                $table->string('status', 20)->default('approved')->index();
            }
            if (! Schema::hasColumn('deposits', 'reference')) {
                $table->string('reference')->nullable();
            }
            if (! Schema::hasColumn('deposits', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (! Schema::hasColumn('deposits', 'review_notes')) {
                $table->text('review_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('deposits')) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table) {
            foreach (['review_notes', 'reviewed_at', 'reviewed_by', 'submitted_by', 'reference', 'status'] as $column) {
                if (Schema::hasColumn('deposits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

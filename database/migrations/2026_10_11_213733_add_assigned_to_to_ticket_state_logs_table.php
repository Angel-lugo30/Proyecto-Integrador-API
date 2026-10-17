<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_state_logs', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('changed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->index(
                ['assigned_to', 'started_at'],
                'ticket_logs_assignee_started_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ticket_state_logs', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex('ticket_logs_assignee_started_index');
            $table->dropColumn('assigned_to');
        });
    }
};
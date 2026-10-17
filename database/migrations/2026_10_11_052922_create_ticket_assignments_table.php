
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'assigned_at']);
            $table->index(['technician_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_assignments');
    }
};

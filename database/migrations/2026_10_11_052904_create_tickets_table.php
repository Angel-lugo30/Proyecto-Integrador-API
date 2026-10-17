
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number', 30)->unique();

            $table->foreignId('requester_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('equipment_id')
                ->constrained('equipment')
                ->restrictOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 180);
            $table->text('description');

            // en_revision, finalizado o inhabilitado.
            $table->string('status', 20)
                ->default('en_revision')
                ->index();

            $table->timestamp('started_at');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['requester_id', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['equipment_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

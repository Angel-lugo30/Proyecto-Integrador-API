
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            $table->string('location', 150)->nullable();
            $table->string('equipment_type', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();

            // available, maintenance o disabled.
            $table->string('status', 20)->default('available')->index();

            $table->timestamps();

            $table->index(['name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};

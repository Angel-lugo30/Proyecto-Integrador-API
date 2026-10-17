
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('employee_number', 30)->unique();
            $table->string('name', 120);

            // admin, user o technician.
            $table->string('role', 20)->default('user')->index();

            $table->string('password');

            $table->boolean('is_active')->default(true)->index();

            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

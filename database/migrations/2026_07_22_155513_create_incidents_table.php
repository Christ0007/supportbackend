// database/migrations/2024_01_01_000006_create_incidents_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->string('category');
            $table->enum('status', [
                'declared',
                'analyzed',
                'taken_over',
                'in_progress',
                'resolved',
                'closed'
            ])->default('declared');
            $table->foreignId('software_solution_id')->constrained();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
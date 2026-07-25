// database/migrations/2024_01_01_000004_create_software_solution_user_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_solution_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_solution_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['software_solution_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_solution_user');
    }
};
// database/migrations/2024_01_01_000005_create_company_software_solution_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_software_solution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('software_solution_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['company_id', 'software_solution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_software_solution');
    }
};
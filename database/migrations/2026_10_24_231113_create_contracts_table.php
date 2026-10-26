<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('executor_id')->constrained('users');
            $table->string('supplier');
            $table->date('start_date');
            $table->unsignedInteger('duration_days');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->string('expense_category')->default('Dori vositalari');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};

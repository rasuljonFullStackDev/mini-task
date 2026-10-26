<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('expense_category');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();

            $table->unique(['fiscal_year', 'expense_category', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};

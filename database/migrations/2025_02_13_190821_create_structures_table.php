<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id')->constrained()->onDelete('cascade');
			$table->string('date');
			$table->string('identifier')->nullable();
			$table->string('label')->nullable();
			$table->string('label_level')->nullable();
			$table->string('label_description')->nullable();
			$table->string('size')->nullable();
			$table->string('structure_reference')->nullable();
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};

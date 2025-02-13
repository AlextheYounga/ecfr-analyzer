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
        Schema::create('title_structures', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id')->constrained()->onDelete('cascade');
			$table->date('date');
			$table->string('identifier')->nullable();
			$table->string('label')->nullable();
			$table->string('label_level')->nullable();
			$table->string('label_description')->nullable();
			$table->integer('size')->nullable();
			$table->string('structure_reference')
			->comment('This points to a JSON file saved in storage')
			->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_structures');
    }
};

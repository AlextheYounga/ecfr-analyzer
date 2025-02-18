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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
			$table->foreignId('parent_id')->nullable();
			$table->string('name');
			$table->string('short_name')->nullable();
			$table->string('display_name')->nullable();
			$table->string('sortable_name')->nullable();
			$table->string('slug');
			$table->integer('word_count')->index()->nullable();
			$table->json('cfr_references')->nullable();	
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};

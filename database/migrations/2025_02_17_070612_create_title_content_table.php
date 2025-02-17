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
        Schema::create('title_content', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id');
			$table->foreignId('entity_id');
			$table->longText('content')->nullable();
			$table->integer('word_count')->nullable();	
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_content');
    }
};

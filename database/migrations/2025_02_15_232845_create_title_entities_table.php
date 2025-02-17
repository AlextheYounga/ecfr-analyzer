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
        Schema::create('title_entities', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id')->constrained()->onDelete('cascade');
			$table->foreignId('parent_id');
			$table->integer('level')->index();
			$table->integer('order_index')->index();
			$table->string('type')->index();
			$table->string('identifier')->nullable();
			$table->string('label')->nullable();
			$table->string('label_level')->nullable();
			$table->string('label_description')->nullable();
			$table->boolean('reserved')->nullable();
			$table->integer('size')->nullable();
			$table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_entities');
    }
};

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
        Schema::create('title_versions', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id')->constrained()->onDelete('cascade');
			$table->string('date');
			$table->string('amendment_date')->nullable();
			$table->string('issue_date')->nullable();
			$table->string('identifier')->nullable();
			$table->string('name')->nullable();
			$table->string('part')->nullable();
			$table->boolean('substantive')->nullable();
			$table->boolean('removed')->nullable();
			$table->string('subpart')->nullable();
			$table->string('title')->nullable();
			$table->string('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_versions');
    }
};

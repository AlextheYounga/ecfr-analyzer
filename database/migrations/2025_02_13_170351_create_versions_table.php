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
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
			$table->foreignId('title_id')->constrained()->onDelete('cascade');
			$table->date('date');
			$table->date('amendment_date')->nullable();
			$table->date('issue_date')->nullable();
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
        Schema::dropIfExists('versions');
    }
};

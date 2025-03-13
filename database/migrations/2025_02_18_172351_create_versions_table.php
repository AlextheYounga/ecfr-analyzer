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
			$table->foreignId('title_id');
			$table->date('date');
			$table->date('amendment_date');
			$table->date('issue_date');
			$table->string('title')->nullable();
			$table->string('type')->index()->nullable();
			$table->string('identifier', 1020)->index()->nullable();
			$table->string('name', 1530)->nullable();
			$table->string('part')->nullable();
			$table->boolean('substantive')->default(false);
			$table->boolean('removed')->default(false);
			$table->string('subpart')->nullable();
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

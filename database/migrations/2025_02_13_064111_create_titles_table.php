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
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
			$table->integer('number');
			$table->string('name');
			$table->date('latest_amended_on')->nullable();
			$table->date('latest_issue_date')->nullable();
			$table->date('up_to_date_as_of')->nullable();
			$table->boolean('reserved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};

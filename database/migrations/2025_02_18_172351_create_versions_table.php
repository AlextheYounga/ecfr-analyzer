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
			$table->foreignId('title_entity_id')->nullable();
			$table->date('date');
			$table->date('amendment_date');
			$table->date('issue_date');
			$table->string('title')->nullable();
			$table->string('type')->nullable();
			$table->string('identifier')->nullable();
			$table->string('name')->nullable();
			$table->string('part')->nullable();
			$table->boolean('substantive')->default(false);
			$table->boolean('removed')->default(false);
			$table->string('subpart')->nullable();
        });
    }

	// "date":"2016-12-23","amendment_date":"2016-12-23","issue_date":"2017-01-01","identifier":"1.1","name":"§ 1.1   Creation and authority.","part":"1","substantive":true,"removed":false,"subpart":"A","title":"40","type":"section"

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};

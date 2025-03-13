<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

	// Run this first in DB
	// CREATE EXTENSION vector;

    public function up(): void
    {
		if (env('DB_CONNECTION') == 'pgsql') {	
			Schema::create('cfr_chunks', function (Blueprint $table) {
				$table->id();
				$table->foreignId('title_id');
				$table->foreignId('content_id');
				$table->string('title_name');
				$table->string('section_name');
				$table->date('issue_date')->nullable();
				$table->text('chunk_text');
			});

			// Manually make vector column
			DB::statement('ALTER TABLE cfr_chunks ADD COLUMN embedding vector(768)');
			DB::statement('CREATE INDEX ON cfr_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);');
		}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
		if (env('DB_CONNECTION') == 'pgsql') {
        	Schema::dropIfExists('cfr_chunks');
		}
    }
};

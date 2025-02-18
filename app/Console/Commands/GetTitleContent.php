<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Title;
use App\Models\TitleContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GetTitleContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the title documents and save the Markdown content to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		TitleContent::truncate();
		$this->runRustParser();
	
		$titles = Title::all();
		foreach ($titles as $title) {
			$contentMap = [];
			$this->info('Parsing title sections for ' . $title->number);
			$titleEntities = $title->entities()->where('type', 'section')->get();
			$titleWords = 0;
			foreach ($titleEntities as $titleEntity) {
				$titleEntityId = $titleEntity->identifier;
				$filePath = 'ecfr/current/documents/markdown/flat/title-' . $title->number . '/' . $titleEntityId . '.md';
				$markdown = Storage::disk('local')->get($filePath);
				$wordCount = $this->countWords($markdown);
				$titleWords += $wordCount;

				$contentRecord = [
					'title_id' => $title->id,
					'title_entity_id' => $titleEntity->id,
					'content' => $markdown,
					'word_count' => $wordCount,
				];
				array_push($contentMap, $contentRecord);
			}

			$chunks = array_chunk($contentMap, 1000);
			foreach ($chunks as $chunk) {
				DB::table('title_contents')->insert($chunk);
			}

			$title->word_count = $titleWords;
			$title->save();
		}

		// Clean up
		$this->info('Cleaning up temporary files');
		shell_exec('rm -rf ' . storage_path('app/private/ecfr/current/documents/markdown/flat'));
    }

	/**
     * Run Rust parser command to convert title XML to Markdown
	 * This will almost certainly become unnecessary. 
     */
	private function runRustParser() {
        $this->info('Parsing title XML documents and converting to Markdown');

		// Check that the Rust script has been compiled
		if (!file_exists(base_path('rust/target/release/title_markdown_parser'))) {
			throw new \Exception('The Rust script has not been compiled. Please run `cargo build --release` in the `rust` directory.');	
		}

		// Run the Rust script with argument
		shell_exec(base_path('rust/target/release/title_markdown_parser flat'));
	}

	private function countWords($text) {
		// Word Count
		$words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
		return count($words);
	}
}

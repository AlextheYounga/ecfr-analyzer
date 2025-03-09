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
		$this->copyXmlToStorage();
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
				$markdown = Storage::disk('storage_drive')->get($filePath);
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

		$this->moveMarkdownToStorageDrive();
		$this->cleanUp();
    }

	// Copy title XML documents to local storage for performance reasons
	private function copyXmlToStorage() {
		$this->warn('Copying title XML documents to local storage');
		$source = Storage::disk('storage_drive')->path('ecfr/current/documents/xml/');
		$destination = Storage::disk('local')->path('ecfr');
		Storage::disk('local')->makeDirectory('ecfr');
		Storage::disk('local')->makeDirectory('ecfr/markdown/flat');
		shell_exec('cp -r ' . $source . ' ' . $destination);
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

	// Don't want to store these huge files on my machine
	private function moveMarkdownToStorageDrive() {
		$source = Storage::disk('local')->path('ecfr/markdown/flat');
		$destination = Storage::disk('storage_drive')->path('ecfr/current/documents/markdown/flat');

		$this->warn('Zipping markdown files...');
		shell_exec("zip -rq $source.zip $source");

		$this->warn('Moving markdown files to storage drive...');
		shell_exec('mv ' . $source . '.zip ' . $destination);
	}

	private function countWords($text) {
		// Word Count
		$words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
		return count($words);
	}

	private function cleanUp() {
		// Clean up
		$this->info('Cleaning up temporary files');
		$source = Storage::disk('local')->path('ecfr');
		shell_exec('rm -r ' . $source);
	}
}

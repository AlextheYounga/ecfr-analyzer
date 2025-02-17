<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;
use Parsedown;

class CountTitleWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:words';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Count the number of words in the ECFR titles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$titles = Title::all();
		$parser = new Parsedown();

		$this->runRustParser();

		foreach($titles as $title) {
			$markdownTitle = 'ecfr/current/documents/markdown/full/title-' . $title->number . '.md';
			$markdownContent = Storage::disk('local')->get($markdownTitle);
			//Convert Markdown to HTML
			$html = $parser->text($markdownContent);
			// Strip HTML tags to get plain text
			$plaintext = strip_tags($html);
			$wordCount = $this->countWords($plaintext);

			$this->info($wordCount . ' words' . ' | ' . 'Title ' . $title->number . ' - ' . $title->name);

			$title->word_count = $wordCount;
			$title->save();

			$entities = $title->entities()->get();
			foreach($entities as $entity) {
				if (empty($entity->content)) {
					continue;
				}
				$wordCount = $this->countWords($entity->content);
				$entity->word_count = $wordCount;
				$entity->save();
			}
		
		}
    }

	private function countWords($text) {
		// Word Count
		$words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
		return count($words);
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
}

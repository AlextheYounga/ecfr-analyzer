<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Title;
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
		$this->runRustParser();
	

		$titles = Title::all();
		foreach ($titles as $title) {
			$this->info('Parsing title sections for ' . $title->number);
			$titleEntities = $title->entities()->where('type', 'section')->get();

			foreach ($titleEntities as $titleEntity) {
				$titleEntityId = $titleEntity->identifier;
				$filePath = 'ecfr/current/documents/markdown/flat/title-' . $title->number . '/' . $titleEntityId . '.md';
				$markdown = Storage::disk('local')->get($filePath);

				$titleEntity->content()->firstOrNew([
					'title_id' => $title->id,
					'entity_id' => $titleEntity->id,
					'content' => $markdown,
				]);

			}
		}
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

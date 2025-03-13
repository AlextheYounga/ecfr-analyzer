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
		$this->mapTitleContent();

		$this->saveToDrive();
    }

	private function mapTitleContent() {
		$titles = Title::all();
		foreach ($titles as $title) {
			$contentMap = [];
			$this->info('Parsing title sections for ' . $title->number);
			$titleEntities = $title->entities()->where('type', 'section')->get();
			$titleWords = 0;

			foreach ($titleEntities as $titleEntity) {
				$titleEntityId = $titleEntity->identifier;
				$filePath = 'ecfr/current/markdown/flat/title-' . $title->number . '/' . $titleEntityId . '.md';
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
	}

	/**
     * Run Rust parser command to convert title XML to Markdown
	 * This will almost certainly become unnecessary. 
     */
	private function runRustParser() {
        $this->info('Parsing title XML documents and converting to Markdown');
		// Run the Rust script with argument
		$input_folder = Storage::disk('storage_drive')->path('ecfr/current'); // Files to convert
		$output_folder = Storage::disk('local')->path('ecfr'); // Where we're going to store the files

		$shell_command = "./scripts/get_title_content_runner";
		$command_inputs = [$shell_command, $input_folder, $output_folder, "2>&1"];
		$command = implode(" ", array_map('escapeshellarg', $command_inputs)); // Escape arguments properly
		
		// Use passthru() for real-time output
		passthru($command, $exit_code);

		if ($exit_code !== 0) {
			throw new \Exception("Failed to convert title XML to Markdown. Exit code: $exit_code");
		}
	}

	/**
     * Run script to save output to drive and cleanup
     */
	private function saveToDrive() {
		$outputFolder = Storage::disk('local')->path('ecfr/markdown/flat'); // Where we're going to store the files
		$driveFolder = Storage::disk('storage_drive')->path('ecfr/current/markdown'); // Where we're going to store the files

		$shell_command = "./scripts/save_output_to_drive";
		$command_inputs = [$shell_command, $outputFolder, $driveFolder, "2>&1"];
		$command = implode(" ", array_map('escapeshellarg', $command_inputs)); // Escape arguments properly
		
		// Use passthru() for real-time output
		passthru($command, $exit_code);

		if ($exit_code !== 0) {
			throw new \Exception("Failed to convert title XML to Markdown. Exit code: $exit_code");
		}
	}

	private function countWords($text) {
		// Word Count
		$words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
		return count($words);
	}
}

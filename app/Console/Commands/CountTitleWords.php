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
		$this->runRustWordCounter();
    }


	/**
     * Run Rust parser command to convert title XML to Markdown
	 * This will almost certainly become unnecessary. 
     */
	private function runRustParser() {
		$this->info('Parsing title XML documents and converting to Markdown...');

		// Check that the Rust script has been compiled
		if (!file_exists(base_path('rust/target/release/title_markdown_parser'))) {
			throw new \Exception('The Rust script has not been compiled. Please run `cargo build --release` in the `rust` directory.');	
		}

		if (file_exists(storage_path('app/private/ecfr/current/documents/markdown/flat'))) {
			$this->info('Already parsed, skipping');
			return;
		}	

		// Run the Rust script with argument
		shell_exec(base_path('rust/target/release/title_markdown_parser flat'));
	}

	/**
     * Run Rust word counter
     */
	private function runRustWordCounter() {
		$this->info('Counting words...');

		// Check that the Rust script has been compiled
		if (!file_exists(base_path('rust/target/release/word_counter'))) {
			throw new \Exception('The Rust script has not been compiled. Please run `cargo build --release` in the `rust` directory.');	
		}

		// Run the Rust script with argument
		shell_exec(base_path('rust/target/release/word_counter'));
	}
}

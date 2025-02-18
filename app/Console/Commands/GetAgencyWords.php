<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agency;

class GetAgencyWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:agency-words';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the word count for each agency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencies = Agency::all();
		foreach ($agencies as $agency) {
			$this->info("Calculating word count for " . $agency->name);	
			$agency->word_count = $agency->getWords();
			$agency->save();
		}	
    }
}

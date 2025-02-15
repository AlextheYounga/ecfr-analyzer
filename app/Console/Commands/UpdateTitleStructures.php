<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;

class UpdateTitleStructures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:structures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR structures from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();
		$titles = Title::all();
	
		foreach($titles as $title) {
			$this->info("Updating title record for " . $title->number);

			if ($title['reserved']) {
				$structure = [];
			} else {
				$titleNumber = (string) $title->number;
				$versionDate = $title->latest_issue_date->format('Y-m-d');
				$structure = $ecfr->fetchStructure($titleNumber, $versionDate);
			}
			
			$title->structure = $structure;
			$title->save();	
		}	
    }
}

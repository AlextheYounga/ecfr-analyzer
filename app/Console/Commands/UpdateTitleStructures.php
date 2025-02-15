<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

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
			$this->info("Saving title structure for Title " . $title->number);

			if ($title['reserved']) {
				$this->info("Title is reserved, skipping");
				continue;
			}

			// Ensure directory exists
			Storage::disk('local')->makeDirectory('ecfr/structure');

			$titleNumber = (string) $title->number;
			$versionDate = $title->latest_issue_date->format('Y-m-d');
			$structure = $ecfr->fetchStructure($titleNumber, $versionDate);
			$filepath = 'ecfr/structure/title-' . $titleNumber . '-structure.json';
			Storage::disk('local')->put($filepath, json_encode($structure));	

			$title->structure_reference = storage_path('app/private/'. $filepath);	
			$title->save();	
		}	
    }
}

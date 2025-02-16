<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;

class GetTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR titles from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();

		// Download Titles
		$titles = $ecfr->fetchTitles();
		
		foreach($titles['titles'] as $title) {
			$structureFilePath = 'ecfr/current/structure/title-' . $title['number'] . '-structure.json';
			Title::updateOrCreate(
				['number' => $title['number']],
				[
					'name' => $title['name'],
					'latest_amended_on' => $title['latest_amended_on'],
					'latest_issue_date' => $title['latest_issue_date'],
					'up_to_date_as_of' => $title['up_to_date_as_of'],
					'reserved' => $title['reserved'],
					'structure_reference' => storage_path('app/private/'. $structureFilePath)
				]
			);	
		}	
    }
}

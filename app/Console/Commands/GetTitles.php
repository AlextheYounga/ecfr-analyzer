<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

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
			$titleNumber = trim($title['number']);
			$structureFilePath = 'ecfr/current/structure/title-' . $titleNumber . '-structure.json';
			Title::updateOrCreate(
				['number' => $titleNumber],
				[
					'name' => trim($title['name']),
					'latest_amended_on' => isset($title['latest_amended_on']) ? trim($title['latest_amended_on']) : null,
					'latest_issue_date' => isset($title['latest_issue_date']) ? trim($title['latest_issue_date']) : null,
					'up_to_date_as_of' => isset($title['up_to_date_as_of']) ? trim($title['up_to_date_as_of']) : null,
					'reserved' => $title['reserved'],
					'structure_reference' => Storage::disk('storage_drive')->path($structureFilePath),
				]
			);	
		}	
		$this->info('Fetched titles.');
    }
}

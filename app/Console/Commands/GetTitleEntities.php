<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GetTitleEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR structures from API and convert them to "title entities"';

	/**
     * A hash map to store the structure records
     */
	protected $hashMap = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();
		$titles = Title::all();
		$this->warn('Truncating TitleEntity table...');
		DB::delete('DELETE FROM title_entities');

		foreach($titles as $title) {
			$this->info("Saving title structure for Title " . $title->number);

			if ($title['reserved']) {
				$this->info("Title is reserved, skipping");
				continue;
			}

			// Ensure directory exists
			Storage::disk('storage_drive')->makeDirectory('ecfr/current/structure');

			// Fetch the structure for the title
			$titleNumber = (string) $title->number;
			$versionDate = $title->latest_issue_date->format('Y-m-d');
			$structure = $ecfr->fetchStructure($titleNumber, $versionDate);

			if (empty($structure)) {
				$this->warn("No structure found for Title " . $titleNumber);
				continue;
			}

			// Save the structure to a file
			$filepath = 'ecfr/current/structure/title-' . $titleNumber . '-structure.json';
			Storage::disk('storage_drive')->put($filepath, $structure);	
			sleep(1);
		}	

		$this->call('db:seed', ['--class' => 'TitleEntitySeeder']);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;

class DownloadECFRStructures extends Command
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
    protected $description = 'Download eCFR title structures from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$ecfr = new ECFRService();
		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);

		// Download Structures
		$dates = [];
		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$titleVersionJsonFile = 'versions/title-' . $titleNumber . '.json';	
			$versionsJsonStorage = Storage::disk('local')->get($titleVersionJsonFile);
			$versions = json_decode($versionsJsonStorage, true);

			foreach($versions['content_versions'] as $version) {
				$versionDate = $version['date'];
				if (in_array($versionDate, $dates)) {
					continue;
				}
				$this->info("Fetching structure for title " . $title['number'] . ' on date ' . $versionDate);

				// Fetch from API
				$structure = $ecfr->fetchStructure($titleNumber, $versionDate);

				$filename = 'structure/title-' . $titleNumber . '-' . $versionDate . '.json';
				Storage::disk('local')->put($filename, json_encode($structure));
				array_push($dates, $versionDate);
				sleep(0.5);
			}
		}
    }
}

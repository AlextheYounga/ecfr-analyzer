<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;

class DownloadECFRDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$ecfr = new ECFRService();
        $titles = $this->getTitles();

		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$versions = $this->getTitleVersions($titleNumber);
			$uniqueDates = array_unique(array_column($versions['content_versions'], 'date'));

			foreach($uniqueDates as $versionDate) {
				$filename = 'documents/title-' . $titleNumber .'/title-' . $titleNumber . '-' . $versionDate . '.json';

				// Check if file already exists
				if (Storage::disk('local')->exists($filename)) {
					$this->info("File already exists for title " . $title['number'] . ' on date ' . $versionDate);
					continue;
				}
				
				// Fetch from API
				$this->info("Fetching document for title " . $title['number'] . ' on date ' . $versionDate);
				$xml = $ecfr->fetchDocument($titleNumber, $versionDate);
				Storage::disk('local')->put($filename, json_encode(simplexml_load_string($xml)));
				sleep(1);
			}
		}
    }

	private function getTitles() {
		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		return $titles;
	}

	private function getTitleVersions($titleNumber) {
		$titleVersionJsonFile = 'versions/title-' . $titleNumber . '.json';	
		$versionsJsonStorage = Storage::disk('local')->get($titleVersionJsonFile);
		$versions = json_decode($versionsJsonStorage, true);
		return $versions;
	}
}

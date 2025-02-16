<?php

namespace App\Console\Commands\Download;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadECFRStructures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:download-structures';

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
		$titles = $this->getTitles();

		// Download Structures
		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$versions = $this->getVersions($titleNumber);
			$uniqueDates = array_unique(array_column($versions['content_versions'], 'date'));

			foreach($uniqueDates as $versionDate) {
				// Fetch from API
				$this->info("Fetching structure for title " . $title['number'] . ' on date ' . $versionDate);
				$structure = $ecfr->fetchStructure($titleNumber, $versionDate);

				$filename = 'structure/title-' . $titleNumber . '-' . $versionDate . '.json';
				$this->zipFile($filename, $structure);
				sleep(0.5);
			}
		}
    }

	private function getTitles() {
		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		return $titles;
	}

	private function getVersions($titleNumber) {
		$versionJsonFile = 'versions/title-' . $titleNumber . '.json';	
		$versionsJsonStorage = Storage::disk('local')->get($versionJsonFile);
		$versions = json_decode($versionsJsonStorage, true);
		return $versions;
	}

	private function zipFile($filename, $data) {
		$zipFilename = $filename . '.zip';
		$zip = new ZipArchive; // Create a zip archive directly in memory or to a file
		$zipPath = storage_path("app/private/{$zipFilename}");

		if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
			// Prepare the JSON data (no need to save it to disk)
			$jsonData = json_encode($data);

			// Add the JSON data as a file in the zip
			$zip->addFromString(basename($filename), $jsonData);

			// Close the zip archive
			$zip->close();
		}
	}
}

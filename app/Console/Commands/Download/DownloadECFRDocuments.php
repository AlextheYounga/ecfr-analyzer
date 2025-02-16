<?php

namespace App\Console\Commands\Download;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
class DownloadECFRDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:download-documents';

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
			$versions = $this->getVersions($titleNumber);
			$uniqueDates = array_unique(array_column($versions['content_versions'], 'date'));

			foreach($uniqueDates as $versionDate) {
				$filename = 'documents/title-' . $titleNumber .'/title-' . $titleNumber . '-' . $versionDate . '.json';

				// Check if file already exists
				if (Storage::disk('local')->exists($filename . '.zip')) {
					$this->warn("File already exists for title " . $title['number'] . ' on date ' . $versionDate);
					continue;
				}

				// Check if we've had problems with this route before
				if ($this->routeInErrorLog($filename)) {
					$this->error("Previous problems downloading title " . $title['number'] . ' on date ' . $versionDate);
					continue;
				}
				
				// Fetch from API
				$xml = $ecfr->fetchDocument($titleNumber, $versionDate);
				if ($xml) {
					$jsonXml = json_encode(simplexml_load_string($xml));
					$this->zipFile($filename, $jsonXml);
					$this->info("Downloaded title " . $title['number'] . ' on date ' . $versionDate);
				} else {
					Log::error($filename);
				}
				sleep(1);
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

	private function routeInErrorLog($filename) {
		$logPath = storage_path('logs/laravel.log');
		if (file_exists($logPath)) {
			$logContents = file_get_contents($logPath);
			if (strpos($logContents, $filename) !== false) {
				return true;
			}
		}
		return false;
	}
}

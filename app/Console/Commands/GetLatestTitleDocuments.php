<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;	
use App\Models\Title;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GetLatestTitleDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:latest-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest title documents from ECFR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('ecfr:titles');	

		$ecfr = new ECFRService();
        $titles = Title::all();

		foreach ($titles as $title) {
			$versionDate = $this->getVersionDate($title);
			if (!$versionDate) {
				$this->warn("No version date found for title " . $title['number']);
				continue;
			}

			$filename = 'documents/latest/title-' . $title->number . '.json';

			// Fetch from API
			$xml = $ecfr->fetchDocument($title->number, $versionDate);
			if ($xml) {
				$jsonXml = json_encode(simplexml_load_string($xml));
				$this->zipFile($filename, $jsonXml);
				$this->info("Downloaded title " . $title['number'] . ' on date ' . $versionDate);
			} else {
				Log::error($filename);
			} 
		}
    }

	private function getVersionDate($title) {
		if ($title->up_to_date_as_of) {
			return $title->up_to_date_as_of->format('Y-m-d');
		} else if ($title->latest_issue_date){
			return $title->latest_issue_date->format('Y-m-d');
		} else if ($title->latest_amended_on) {
			return $title->latest_amended_on->format('Y-m-d');
		} else {
			return null;
		}
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;	
use App\Models\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class GetTitleDocuments extends Command
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
    protected $description = 'Fetch latest title documents from ECFR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$ecfr = new ECFRService();
        $titles = Title::all();

		if (!$titles) {
			throw new \Exception("No titles found. Run php artisan ecfr:titles");	
		}

		// Ensure directory exists
		Storage::disk('local')->makeDirectory('ecfr/current/documents/xml');
		$storageDrive = env("STORAGE_DRIVE") . '/ecfr/xml';

		foreach ($titles as $title) {
			$filename = 'ecfr/current/documents/xml/title-' . $title->number . '.xml';
			if (Storage::disk('local')->exists($filename)) {
				$this->info("Title " . $title['number'] . " already downloaded, skipping");
				continue;
			}	

			$versionDate = $this->getVersionDate($title);
			if (!$versionDate) {
				$this->warn("No version date found for title " . $title['number']);
				continue;
			}

			// Fetch from API
			$xml = $ecfr->fetchDocument($title->number, $versionDate);
			if ($xml) {
				Storage::disk('local')->put($filename, $xml);
				$this->info("Downloaded title " . $title['number'] . ' on date ' . $versionDate);
				if (\file_exists($storageDrive . '/title-' . $title->number)) {
					$filename = '/title-' . $title->number . '-' . $versionDate . '.xml';
					\file_put_contents($storageDrive . '/title-' . $title->number . $filename, $xml);
				}
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
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;	
use App\Models\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class GetCurrentTitleDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:current';

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

			$filename = 'ecfr/current/title-' . $title->number . '.xml';

			// Fetch from API
			$xml = $ecfr->fetchDocument($title->number, $versionDate);
			if ($xml) {
				Storage::disk('local')->put($filename, $xml);
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
}

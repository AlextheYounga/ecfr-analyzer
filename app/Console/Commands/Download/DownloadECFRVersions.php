<?php

namespace App\Console\Commands\Download;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;
class DownloadECFRVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:download-versions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR title versions from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$ecfr = new ECFRService();
		$titles = $this->getTitles();
		
		// Download Versions
		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$this->info("Fetching versions for title " . $title['number']);

			// Fetch from API
			$version = $ecfr->fetchVersions($titleNumber);
			$filename = 'versions/title-' . $titleNumber . '.json';
			Storage::disk('local')->put($filename, json_encode($version));
			sleep(0.5);
		}
    }

	private function getTitles() {
		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		return $titles;
	}
}

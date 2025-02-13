<?php

namespace App\Console\Commands;

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
    protected $signature = 'ecfr:versions';

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
		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		
		// Download Versions
		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$this->info("Fetching versions for title " . $title['number']);

			// Fetch from API
			$version = $ecfr->fetchVersion($titleNumber);
			Storage::disk('local')->put('versions/title-' . $title['number'] . '.json', json_encode($version));
			sleep(0.5);
		}
    }
}

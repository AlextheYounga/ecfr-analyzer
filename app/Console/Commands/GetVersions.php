<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Version;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

class GetVersions extends Command
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
    protected $description = 'Get all versions from the eCFR API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$ecfr = new ECFRService();
		$titles = Title::all();
		Version::truncate();
		
		// Download Versions
		foreach($titles as $title) {
			$this->info("Fetching versions for title " . $title->number);

			// Fetch from API
			$versions = $ecfr->fetchVersions($title->number);

			// Ensure directory exists
			Storage::disk('storage_drive')->makeDirectory('ecfr/current/versions');

			// Save to disk
			$filename = 'ecfr/current/versions/title-'. $title->number . '-versions.json';
			Storage::disk('storage_drive')->put($filename, json_encode($versions));

			sleep(0.5);
		}

		$this->call('db:seed', ['--class' => 'VersionSeeder']);
    }


}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;	
use App\Models\Title;
use App\Models\Version;

class GetTitleVersions extends Command
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
    protected $description = 'Update database with latest ECFR versions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $titles = Title::all();
		$ecfr = new ECFRService();

		foreach($titles as $title) {
			dd($title->latestVersion());
			$latestVersion = $title->latestVersion();
			$latestVersionDate = $latestVersion->date;
			$versions = $ecfr->fetchVersions($title->number);

			foreach($versions['content_versions'] as $version) {
				// Only save later version dates than our last
				dd($version['date'], $latestVersionDate);
				if ($version['date'] <= $latestVersionDate) {
					continue;
				}

				$this->info("Updating version " . $version['identifier'] . ' for title ' . $title->number);
				$version = Version::updateOrCreate(
					[
						'title_id' => $title->id,
						'date' => $version['date'],
					],
					[
						'identifier' => $version['identifier'],
						'amendment_date' => $version['amendment_date'],
						'issue_date' => $version['issue_date'],
						'name' => $version['name'],
						'part' => $version['part'],
						'substantive' => $version['substantive'],
						'removed' => $version['removed'],
						'subpart' => $version['subpart'],
						'title' => $version['title'],
						'type' => $version['type'],
					]
				);
			}	
		}
    }
}

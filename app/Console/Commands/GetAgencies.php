<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;	
use App\Models\Title;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Agency;

class GetAgencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:agencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR agencies from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();
		$agencies = $ecfr->fetchAgencies();

		foreach($agencies['agencies'] as $agency) {
			$this->info("Saving agency record for " . $agency['name']);

			$agencyRecord = $this->saveAgency($agency);	
			if ($agency['children']) {
				$this->saveChildren($agency['children'], $agencyRecord->id);	
			}
		}

    }

	private function saveAgency($agency, $parent_id = 0) {
		return Agency::updateOrCreate([
			'slug' => $agency['slug'],
		],
		[
			'parent_id' => $parent_id,
			'name' => $agency['name'],
			'short_name' => $agency['short_name'],
			'display_name' => $agency['display_name'],
			'sortable_name' => $agency['sortable_name'],
			'cfr_references' => $agency['cfr_references'],
		]);
	}

	private function saveChildren($children, $parent_id) {
		foreach($children as $child) {
			$agency = $this->saveAgency($child, $parent_id);
			if (isset($child['children'])) {
				$this->saveChildren($child['children'], $agency->id);
			}
		}
	}
}

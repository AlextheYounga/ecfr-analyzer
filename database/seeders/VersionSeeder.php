<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Version;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
		Version::truncate();
		$titles = Title::all();	

		// Download Versions
		$inserted = 0;
		foreach($titles as $title) {
			$versionRecords = [];

			// Ensure directory exists
			$versionFile = Storage::disk('storage_drive')->get('ecfr/current/versions/title-'. $title->number . '-versions.json');
			$versions = json_decode($versionFile, true);

			foreach($versions['content_versions'] as $version) {
				array_push($versionRecords, [
					'title_id' => $title->id,	
					'date' => isset($version['date']) ? trim($version['date']) : null,
					'amendment_date' => isset($version['amendment_date']) ? trim($version['amendment_date']) : null,
					'issue_date' => isset($version['issue_date']) ? trim($version['issue_date']) : null,
					'title' => isset($version['title']) ? trim($version['title']) : null,
					'type' => trim($version['type']),
					'identifier' => trim($version['identifier']),
					'name' => isset($version['name']) ? trim($version['name']) : null,
					'part' => isset($version['part']) ? trim($version['part']) : null,
					'substantive' => isset($version['substantive']) ? (boolean) $version['substantive'] : false,
					'removed' => isset($version['removed']) ? (boolean) $version['removed'] : false,
					'subpart' => isset($version['subpart']) ? trim($version['subpart']) : null,
				]);
			}
	
			$chunks = array_chunk($versionRecords, 1000);
			foreach($chunks as $chunk) {
				$inserted += count($chunk);
				echo "Inserted " . $inserted . " records\n";
				DB::table('versions')->insert($chunk);
			}

			// $properties = $title->properties != null ? $title->properties : [];
			$title->properties = array_merge($title->properties ?? [], [
				'amendments' => count($versions['content_versions']),
			]);
			$title->save();
		}
    }
}

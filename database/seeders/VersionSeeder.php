<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Version;
use App\Models\Title;
use App\Models\TitleEntity;
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
					'title_entity_id' => $this->getTitleEntity($title, $version['identifier'], $version['type']),
					'date' => $version['date'],
					'amendment_date' => $version['amendment_date'],
					'issue_date' => $version['issue_date'],
					'title' => $version['title'],
					'type' => $version['type'],
					'identifier' => $version['identifier'],
					'name' => $version['name'],
					'part' => $version['part'],
					'substantive' => $version['substantive'],
					'removed' => $version['removed'],
					'subpart' => $version['subpart'],
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

	private function getTitleEntity($title, $identifier, $type) {
		$titleEntity = TitleEntity::where('title_id', $title->id)
			->where('identifier', $identifier)
			->where('type', $type)
			->first();
		
		return $titleEntity != null ? $titleEntity->id : null;
	}
}

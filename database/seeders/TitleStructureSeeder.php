<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\TitleStructure;

class TitleStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		TitleStructure::truncate();

		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		$dates = [];

		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$titleVersionJsonFile = 'versions/title-' . $titleNumber . '.json';	
			$versionsJsonStorage = Storage::disk('local')->get($titleVersionJsonFile);
			$versions = json_decode($versionsJsonStorage, true);

			foreach($versions['content_versions'] as $version) {
				$versionDate = $version['date'];
				if (in_array($versionDate, $dates)) {
					continue;
				}

				$filename = 'structure/title-' . $titleNumber . '-' . $versionDate . '.json';
				$structureJsonStorage = Storage::disk('local')->get($filename);
				$structure = json_decode($structureJsonStorage, true);
				if (empty($structure)) {
					continue;
				}

				echo "Seeding title structure: " . $titleNumber . ' on date ' . $versionDate . "\n";

				TitleStructure::create([
					'title_id' => $titleNumber,
					'date' => $versionDate,
					'identifier' => $structure['identifier'],
					'label' => $structure['label'],
					'label_level' => $structure['label_level'],
					'label_description' => $structure['label_description'],
					'size' => $structure['size'] ?? null,
					'structure_reference' => $filename,
				]);

				array_push($dates, $versionDate);
			}
		}
    }
}

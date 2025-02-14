<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Structure;
use ZipArchive;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		Structure::truncate();

		$titleJsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($titleJsonStorage, true);
		$dates = [];

		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			$VersionJsonFile = 'versions/title-' . $titleNumber . '.json';	
			$versionsJsonStorage = Storage::disk('local')->get($VersionJsonFile);
			$versions = json_decode($versionsJsonStorage, true);

			foreach($versions['content_versions'] as $index => $version) {
				$versionDate = $version['date'];
				if (in_array($versionDate, $dates)) {
					continue;
				}

				$filename = 'structure/title-' . $titleNumber . '-' . $versionDate . '.json';
				$structureZipFile = storage_path('app/private/' . $filename . '.zip');	
				$structure = $this->readZipFile($structureZipFile);
				if (empty($structure)) {
					continue;
				}

				echo "Seeding title structure: " . $titleNumber . ' on date ' . $versionDate . "\n";

				Structure::create([
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

	private function readZipFile($zipFile) {
		$zip = new ZipArchive;
		if ($zip->open($zipFile) === TRUE) {
			$fileName = $zip->getNameIndex(0); // first file inside zip
			$fileContent = $zip->getFromName($fileName);
			$zip->close();
		
			$jsonData = json_decode($fileContent, true);
			if ($jsonData !== null) {
				return $jsonData;
			} else {
				return [];
			}
		} else {
			return [];
		}
	}
}

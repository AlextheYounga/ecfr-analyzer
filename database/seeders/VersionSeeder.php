<?php

namespace Database\Seeders;

use App\Models\Version;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		Version::truncate();

		$jsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($jsonStorage, true);

		foreach($titles['titles'] as $title) {
			$titleNumber = $title['number'];
			echo "Seeding title versions: " . $titleNumber . "\n";
			$jsonStorage = Storage::disk('local')->get('versions/title-' . $titleNumber . '.json');
			$versions = json_decode($jsonStorage, true);

			$data = [];
			foreach ($versions['content_versions'] as $version) {
				array_push($data, [
					'title_id' => $titleNumber,
					...$version
				]);
			}

			// Split into chunks for bulk insert
			$chunks = array_chunk($data, 999);
			foreach($chunks as $chunk) {
				Version::insert($chunk);
			}
		}
    }
}

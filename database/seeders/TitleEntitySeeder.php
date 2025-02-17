<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Title;
use App\Models\TitleEntity;
use Illuminate\Support\Facades\DB;

class TitleEntitySeeder extends Seeder
{

	protected $hashMap = [];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		TitleEntity::truncate();
		$titles = Title::all();
	
		foreach($titles as $title) {
			echo "Mapping title structure for Title " . $title->number . "\n";

			if ($title->reserved) {
				echo "Title is reserved, skipping\n";
				continue;
			}

			$structureFile = \file_get_contents($title->structure_reference);	
			$structure = json_decode($structureFile, true);

			// Save title structure with children
			$this->mapEntities($title->id, $structure, 0, 0, 0);
		}	

		$this->massInsert();
    }

	private function mapEntities($titleId, $structure, $parentId, $level, $orderIndex) {
		$record = [
			'id' => count($this->hashMap) == 0 ? 1 : count($this->hashMap) + 1,
			'title_id' => $titleId,
			'parent_id' => $parentId,
			'level' => $level,
			'order_index' => $orderIndex,
			'identifier' => isset($structure['identifier']) ? trim($structure['identifier']) : null,
			'label' => isset($structure['label']) ? trim($structure['label']) : null,
			'label_level' => isset($structure['label_level']) ? trim($structure['label_level']) : null,
			'label_description' => isset($structure['label_description']) ? trim($structure['label_description']) : null,
			'reserved' => $structure['reserved'] ?? false,
			'type' => $structure['type'] ?? 'unknown',
			'size' => $structure['size'] ?? null,
			'created_at' => now(),
			'updated_at' => now(),
		];

		array_push($this->hashMap, $record);

		if (isset($structure['children'])) {
			foreach($structure['children'] as $index => $child) {
				$this->mapEntities($titleId, $child, $record['id'], $level + 1, $index);
			}
		}
	}

	private function massInsert() {
		echo "Inserting " . count($this->hashMap) . " records...\n";
		$chunks = array_chunk($this->hashMap, 1000);	
		$inserted = 0;
		foreach ($chunks as $chunk) {
			DB::table('title_entities')->insert($chunk);
			$inserted += count($chunk);
			echo "Inserted $inserted records\n";
		}
	}
}

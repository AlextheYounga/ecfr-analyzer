<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use App\Models\Title;
use App\Models\TitleEntity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GetTitleEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR structures from API';

	/**
     * A hash map to store the structure records
     */
	protected $hashMap = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();
		$titles = Title::all();
		TitleEntity::truncate();
	
		foreach($titles as $title) {
			$this->info("Saving title structure for Title " . $title->number);

			if ($title['reserved']) {
				$this->info("Title is reserved, skipping");
				continue;
			}

			// Ensure directory exists
			Storage::disk('local')->makeDirectory('ecfr/current/structure');

			// Fetch the structure for the title
			$titleNumber = (string) $title->number;
			$versionDate = $title->latest_issue_date->format('Y-m-d');
			$structure = $ecfr->fetchStructure($titleNumber, $versionDate);

			if (empty($structure)) {
				$this->warn("No structure found for Title " . $titleNumber);
				continue;
			}

			// Save the structure to a file and add file reference to Title table
			$filepath = 'ecfr/current/structure/title-' . $titleNumber . '-structure.json';
			Storage::disk('local')->put($filepath, json_encode($structure));	
			$title->structure_reference = storage_path('app/private/'. $filepath);	
			$title->save();	

			// Save title structure with children
			$this->mapEntities($title->id, $structure, 0, 0, 0);
			sleep(1);
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
			'reserved' => $structure['reserved']?? false,
			'type' => $structure['type'],
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

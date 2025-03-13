<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TitleEntity;
use App\Models\Agency;
use App\Models\Title;
use Illuminate\Support\Facades\DB;


class GetAgencyTitleRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:agency-titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save agency title entity relations to the database';

    /**
     * Execute the console command.
     */

	protected $entities;

    public function handle()
    {
		DB::table('agency_title_entity')->truncate();	
		$agencies = Agency::all();
		$this->entities = TitleEntity::select('id', 'title_id', 'type', 'identifier')
			->get()
			->keyBy(function($entity) {
				return implode('-', [
					$entity->title_id,
					$entity->type,
					$entity->identifier,
				]);
			});
			
		foreach($agencies as $agency) {
			$this->info("Saving title_entity relations for " . $agency->id . " " . $agency->name);
			$references = $agency->cfr_references;
			$agencyEntities = [];
	
			foreach($references as $reference) {
				$keys = array_keys($reference);
				$lowestLevel = $keys[count($keys) - 1];
				$referenceEntity = $this->getReferenceEntity($reference['title'], $lowestLevel, $reference[$lowestLevel]);

				if (! $referenceEntity) {
					$this->error("Title entity not found for title " . $reference['title'] . " " . $lowestLevel . " " . $reference[$lowestLevel]);
					continue;
				}
					
				array_push($agencyEntities, [
					'agency_id' => $agency->id,
					'title_entity_id' => $referenceEntity->id,
				]);
	
				$entityChildren = $referenceEntity->getAllChildren();
				foreach ($entityChildren as $child) {
					$agencyEntity = [
						'agency_id' => $agency->id,
						'title_entity_id' => $child->id,
					];	
					if (!in_array($agencyEntity, $agencyEntities)) {
						array_push($agencyEntities, $agencyEntity);
					}
				}
			}
	
			$chunks = array_chunk($agencyEntities, 1000);
			foreach($chunks as $chunk) {
				DB::table('agency_title_entity')->insert($chunk);
			}
		}
    }

	private function getReferenceEntity($titleId, $type, $identifier) {
		$title = Title::find((int) $titleId);

		if (! $title) {
			$this->error("Title not found for " . $titleId);
		}

		$lookup = implode("-", [
			'title_id' => $title->id,
			'type' => $type,
			'identifier' => (string) $identifier,
		]);

		$referenceEntity = $this->entities[$lookup] ?? null;

		return $referenceEntity;
	}
}

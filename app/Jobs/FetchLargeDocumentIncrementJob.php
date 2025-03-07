<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class FetchLargeDocumentIncrementJob implements ShouldQueue
{
    use Queueable;

	public $titleNumber;
	public $versionDate;
	public $type;
	public $instanceId;
	public $storageDrive;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
		$this->type = 'part';
		$this->instanceId = $titleNumber . '-' . $versionDate;
		$this->storageDrive = env("STORAGE_DRIVE") . '/ecfr';
    }

	/**
	* Calculate the number of seconds to wait before retrying the job.
	*
	* @return array<int, int>
	*/
	public function backoff(): array
	{
		return [3, 60, 120];
	}

	/**
	 * Get the middleware the job should pass through.
	 *
	 * @return array<int, object>
	 */
	public function middleware(): array
	{
		return [new WithoutOverlapping($this->instanceId)];
	}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
		$ecfr = new ECFRService();
		$structureFolder = $this->storageDrive . '/structure';
		$structureFile = $structureFolder . '/title-' . $this->titleNumber . '-' . $this->versionDate . '-' . 'structure.json';	

		if (!file_exists($structureFile)) {	
			$structureJson = $ecfr->fetchStructure($this->titleNumber, $this->versionDate);
			if (isset($structureJson['error'])) {
				$this->fail($structureJson['error']);
				return;
			}
			// Ensure file path exists
			if (!file_exists($structureFolder)) {
				mkdir($structureFolder, 0777, true);
			}
			\file_put_contents($structureFile, \json_encode($structureJson));
		}

		$structure = json_decode(file_get_contents($structureFile), true);
		$stuctureEntities = $this->getStructureEntities($structure);
		$folder = 'xml/title-' . $this->titleNumber . '/' . $this->versionDate;

		foreach($stuctureEntities as $entity) {
			$increment = $entity['type'] . '-' . $entity['identifier'];
			$filename = '_title-' . $this->titleNumber . '-' . $this->versionDate . '-' . $increment . '.xml';
			$filepath = $this->storageDrive . '/' . $folder . '/' . $filename;

			// Ensure file path exists
			if (!file_exists($this->storageDrive . '/' . $folder)) {
				mkdir($this->storageDrive . '/' . $folder, 0777, true);
			}

			if (file_exists($filepath . '.zip') || $this->isFailedJob()) {
				return;
			}

			// Fetch from API
			$xml = $ecfr->fetchDocumentIncrement(
				$this->titleNumber,
				$this->versionDate,
				$this->type,
				$entity['identifier']
			);

			if (is_array($xml) && isset($xml['error'])) {
				$this->fail($xml['error']);
				return;
			}
			
			$this->zipFile($filepath, $xml);
		}
    }


	private function getStructureEntities($structure, $entities = []) {
		if ($structure['type'] == $this->type) {
			$structureItem = [
				...$structure,
				'children' => []
			];
			$entities[] = $structureItem;
		}
		if (isset($structure['children'])) {
			foreach($structure['children'] as $child) {
				$entities = $this->getStructureEntities($child, $entities);
			}
		}
		return $entities;
	}

	private function zipFile($filepath, $content) {
		$zip = new ZipArchive();
		$zip->open($filepath . '.zip', ZipArchive::CREATE);
		$zip->addFromString(basename($filepath), $content);
		$zip->close();
	}

	private function isFailedJob() {
		try {
			$failedJobs = DB::table('failed_jobs')->get();
			foreach($failedJobs as $job) {
				$jobClass = unserialize(json_decode($job->payload, true)['data']['command']);
				if ($jobClass->instanceId == $this->instanceId) {
					return true;
				}
			}
			return false;
		} catch(\Exception $e) {
			return false;
		}
	}
}

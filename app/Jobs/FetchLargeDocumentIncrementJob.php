<?php

namespace App\Jobs;

use App\Models\TitleEntity;
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
		$this->storageDrive = env("STORAGE_DRIVE");
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
		$structureFolder = $this->storageDrive . '/structure/title-' . $this->titleNumber;
		$structureFile = $structureFolder . '/' . $this->titleNumber . '-' . $this->versionDate . '-' . 'structure.json';	

		if (!file_exists($structureFile)) {	
			$structureJson = $ecfr->fetchStructure($this->titleNumber, $this->versionDate);
			if (gettype($structureJson) == "array" && isset($structureJson['error'])) {
				$this->fail($structureJson['error']);
			}
			\file_put_contents($structureFile, \json_encode($structureJson));
		}

		$structure = json_decode(file_get_contents($structureFile), true);
		$stuctureEntities = $this->getStructureEntities($structure);
		$folder = 'xml/title-' . $this->titleNumber . '/' . $this->versionDate;
		
		foreach($stuctureEntities as $entity) {
			$increment = $this->type . '-' . $entity->identifier;
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
				$entity->identifier
			);

			if (gettype($xml) == "array" && isset($xml['error'])) {
				$this->fail($xml['error']);
			}
			
			$this->zipFile($filepath, $xml);
			sleep(1);
		}
    }

	private function getStructureEntities($structure, $entities = []) {
		foreach($structure as $entity) {
			if ($entity['type'] == $this->type) {
				$entities[] = $entity;
			}
			if (isset($entity['children'])) {
				$entities = $this->getStructureEntities($entity['children'], $entities);
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

	private function convertRomanNumeral($identifier) {
		$romans = array(
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1,
		);
		
		$result = 0;
		foreach ($romans as $key => $value) {
			while (strpos($identifier, $key) === 0) {
				$result += $value;
				$identifier = substr($identifier, strlen($key));
			}
		}
		return $result;
	}
}

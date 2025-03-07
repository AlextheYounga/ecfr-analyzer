<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\TitleEntity;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class FetchHistoricalDocumentJob implements ShouldQueue
{
    use Queueable;

	public $titleNumber;
	public $versionDate;
	public $instanceId;
	public $storageDrive;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
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
		$folder = 'xml/title-' . $this->titleNumber;
		$filename = 'title-' . $this->titleNumber . '-' . $this->versionDate . '.xml';
		$filepath = $this->storageDrive . '/' . $folder . '/' . $filename;


		// Ensure file path exists
		if (!file_exists($this->storageDrive . '/' . $folder)) {
			mkdir($this->storageDrive . '/' . $folder, 0777, true);
		}

		if (file_exists($filepath . '.zip') || $this->isFailedJob()) {
			return;
		}

		// Fetch from API
		$xml = $ecfr->fetchDocument($this->titleNumber, $this->versionDate);

		if (gettype($xml) == "array" && isset($xml['error'])) {
			$this->fail($xml['error']);
		}
		
		$this->zipFile($filepath, $xml);
		sleep(1);
    }

	private function zipFile($filepath, $xml) {
		$zip = new ZipArchive();
		$zip->open($filepath . '.zip', ZipArchive::CREATE);
		$zip->addFromString(basename($filepath), $xml);
		$zip->close();
	}

	private function getTitleSize() {
		$titleEntity = TitleEntity::where('identifier', (string) $this->titleNumber)
			->where('type', 'title')
			->first();
		$size = $titleEntity->size;
		return $size;
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

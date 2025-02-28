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
	public $identifier;
	public $instanceId;
	public $storageDrive;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate, $type, $identifier)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
		$this->type = $type;
		$this->instanceId = $titleNumber . '-' . $versionDate . '-' . $type . '-' . $identifier;
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
		$folder = 'title-' . $this->titleNumber;
		$increment = $this->type . '-' . $this->identifier;
		// Underscore so we know it's an increment file
		$filename = 'title-' . $this->titleNumber . '-' . $this->versionDate . '-' . $increment . '.xml';
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
			$this->identifier
		);

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

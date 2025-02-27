<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use ZipArchive;

class FetchHistoricalDocumentJob implements ShouldQueue
{
    use Queueable;

	protected $titleNumber;
	protected $versionDate;
	protected $instanceId;
	protected $storageDrive;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
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
		$folder = 'title-' . $this->titleNumber;
		$filename = 'title-' . $this->titleNumber . '-' . $this->versionDate . '.xml';
		$filepath = $this->storageDrive . '/' . $folder . '/' . $filename;

		if ((int) $this->titleNumber == 40) {
			// We are skipping title 40 for now because it is too large and will have to be handled differently
			return;
		}

		// Ensure file path exists
		if (!file_exists($this->storageDrive . '/' . $folder)) {
			mkdir($this->storageDrive . '/' . $folder, 0777, true);
		}

		// If the file already exists, skip
		if (file_exists($filepath . '.zip')) {
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
}

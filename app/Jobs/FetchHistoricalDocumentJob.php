<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\TitleEntity;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

class FetchHistoricalDocumentJob implements ShouldQueue
{
    use Queueable;

	public $titleNumber;
	public $versionDate;
	public $instanceId;

	// Set the number of times the job may be attempted.
	public $tries = 3;
	// Set the number of seconds the job can run before timing out.
	public $timeout = 6000;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
		$this->instanceId = $titleNumber . '-' . $versionDate;
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
		$filepath = $this->constructFilePath();

		Storage::disk('storage_drive')->makeDirectory(dirname($filepath));
		if (Storage::disk('storage_drive')->exists($filepath . 'zip')) {
			return;
		}

		// Fetch from API
		$xml = $ecfr->fetchDocument($this->titleNumber, $this->versionDate);

		if (gettype($xml) == "array" && isset($xml['error'])) {
			$this->fail($xml['error']);
		}
		
		$this->zipFile(
			Storage::disk('storage_drive')->path($filepath), 
			$xml
		);

		sleep(1);
    }

	private function constructFilePath() {
		$folder = 'historical/xml/title-' . $this->titleNumber;
		$filename = 'title-' . $this->titleNumber . '-' . $this->versionDate . '.xml';
		$filepath = "ecfr/$folder/$filename";
		return $filepath;
	}

	private function zipFile($filepath, $xml) {
		$zip = new ZipArchive();
		$zip->open($filepath . '.zip', ZipArchive::CREATE);
		$zip->addFromString(basename($filepath), $xml);
		$zip->close();
	}
}

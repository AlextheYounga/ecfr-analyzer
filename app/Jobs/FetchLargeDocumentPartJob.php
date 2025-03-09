<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class FetchLargeDocumentPartJob implements ShouldQueue
{
    use Queueable;

	public $titleNumber;
	public $versionDate;
	public $part;
	public $instanceId;
	public $storageDrive;

	// Set the number of times the job may be attempted.
	public $tries = 3;
	// Set the number of seconds the job can run before timing out.
	public $timeout = 6000;

    /**
     * Create a new job instance.
     */
    public function __construct($titleNumber, $versionDate, $part)
    {
        $this->titleNumber = $titleNumber;
		$this->versionDate = $versionDate;
		$this->part = $part;
		$this->instanceId = $titleNumber . '-' . $versionDate . '-' . $part;
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
		$filepath = $this->constructFilePath();

		// Ensure file path exists
		if (!file_exists(dirname($filepath))) {
			mkdir(dirname($filepath), 0777, true);
		}

		if (file_exists($filepath . '.zip')) {
			return;
		}

		// Fetch from API
		$xml = $ecfr->fetchDocumentIncrement(
			$this->titleNumber,
			$this->versionDate,
			'part',
			$this->part
		);

		if (is_array($xml) && isset($xml['error'])) {
			$this->fail($xml['error']);
			return;
		}
		
		$this->zipFile($filepath, $xml);
    }


	private function constructFilePath() {
		$folder = 'xml/title-' . $this->titleNumber . '/partials/' . $this->versionDate;
		$part = 'part-' . $this->part;
		$filename = '_title-' . $this->titleNumber . '-' . $this->versionDate . '-' . $part . '.xml';
		$filepath = $this->storageDrive . '/' . $folder . '/' . $filename;
		return $filepath;
	}

	private function zipFile($filepath, $content) {
		$zip = new ZipArchive();
		$zip->open($filepath . '.zip', ZipArchive::CREATE);
		$zip->addFromString(basename($filepath), $content);
		$zip->close();
	}
}

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ECFRService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;

class FetchCurrentDocumentJob implements ShouldQueue
{
    use Queueable;

	public $title;

	// Set the number of times the job may be attempted.
	public $tries = 3;
	// Set the number of seconds the job can run before timing out.
	public $timeout = 6000;

    /**
     * Create a new job instance.
     */
    public function __construct($title)
    {
        $this->title = $title;
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
		return [new WithoutOverlapping($this->title->id)];
	}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
		$ecfr = new ECFRService();

		// Ensure directory exists
		$xmlFolder = 'ecfr/current/documents/xml';
		$filename = 'title-' . $this->title->number . '.xml';
		$filepath = "$xmlFolder/$filename";

		Storage::disk('storage_drive')->makeDirectory($xmlFolder);
		if (Storage::disk('storage_drive')->exists($filepath)) {
			return;
		}

		// Create structure folder
		$structureFolder = 'ecfr/current/structure';
		Storage::disk('storage_drive')->makeDirectory($structureFolder);

		// Fetch from API
		$versionDate = $this->title->latest_issue_date->format('Y-m-d');
		$xml = $ecfr->fetchDocument($this->title->number, $versionDate);
		$json = $ecfr->fetchStructure($this->title->number, $versionDate);

		// Save to storage folder
		Storage::disk('storage_drive')->put($filepath, $xml);
		Storage::disk('storage_drive')->put("$structureFolder/title-{$this->title->number}-structure.json", $json);
    }
}


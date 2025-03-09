<?php

namespace App\Console\Commands;

use App\Models\Title;
use Illuminate\Console\Command;
use App\Jobs\FetchHistoricalDocumentJob;
use App\Jobs\FetchLargeDocumentPartJob;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;

class CompileHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launches jobs to fetch historical documents for all titles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->call('queue:clear');
		$this->call('queue:flush');
	
		$titles = Title::all();
		foreach($titles as $title) {
			if ($title->large) {
				$this->createLargeDocumentsJobs($title);
				continue;
			}
			$this->createDocumentsJobs($title);
		}
    }

	private function createDocumentsJobs($title) {
		foreach($title->versionDates() as $version) {
			$formattedDate = $version->issue_date->format('Y-m-d');
			if ($this->fileAlreadyDownloaded($title->number, $formattedDate)) {
				continue;
			}
			FetchHistoricalDocumentJob::dispatch($title->number, $formattedDate);
		}
	}

	private function createLargeDocumentsJobs($title) {
		$versions = Version::where('title_id', $title->id)
			->select('issue_date', 'part')
			->distinct()
			->get();
			
		foreach($versions as $version) {
			$formattedDate = $version->issue_date->format('Y-m-d');
			if ($this->largeFileAlreadyDownloaded($title->number, $formattedDate, $version->part)) {
				continue;
			}
			FetchLargeDocumentPartJob::dispatch($title->number, $formattedDate, $version->part);	
		}
	}
	
	private function fileAlreadyDownloaded($titleNumber, $versionDate) {
		$folder = 'historical/xml/title-' . $titleNumber;
		$filename = 'title-' . $titleNumber . '-' . $versionDate . '.xml';
		$filepath = "ecfr/$folder/$filename.zip";
		
		if (Storage::disk('storage_drive')->exists($filepath)) {
			return true;
		}
		return false;
	}

	private function largeFileAlreadyDownloaded($titleNumber, $versionDate, $part) {
		$folder = 'historical/xml/title-' . $titleNumber . "/partials/" . $versionDate;
		$part = 'part-' . $part;
		$filename = '_title-' . $titleNumber . '-' . $versionDate . '-' . $part . '.xml';
		$filepath = "ecfr/$folder/$filename.zip";

		if (Storage::disk('storage_drive')->exists($filepath)) {
			return true;
		}
		return false;
	}
}

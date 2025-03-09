<?php

namespace App\Console\Commands;

use App\Models\Title;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\FetchHistoricalDocumentJob;
use App\Jobs\FetchLargeDocumentPartJob;
use App\Models\Version;

class CompileHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:compile-history';

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
		DB::table('jobs')->truncate();
		DB::table('failed_jobs')->truncate();

		$largeTitles = ['40']; 		// Title 40 is too large, we will have to handle this one separately
		$titles = Title::all();
		foreach($titles as $title) {
			if (in_array($title->number, $largeTitles)) {
				$this->createLargeDocumentsJobs($title);
				continue;
			}
			$this->createDocumentsJobs($title);
		}
    }

	private function createDocumentsJobs($title) {
		foreach($title->versionDates() as $date) {
			$formattedDate = $date->date->format('Y-m-d');
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
		$storageDrive = env("STORAGE_DRIVE") . '/ecfr';
		$folder = '/xml/title-' . $titleNumber;
		$filename = '/title-' . $titleNumber . '-' . $versionDate . '.xml';
		$filepath = $storageDrive . $folder . $filename;

		if (file_exists($filepath . '.zip')) {
			return true;
		}
		return false;
	}

	private function largeFileAlreadyDownloaded($titleNumber, $versionDate, $part) {
		$storageDrive = env("STORAGE_DRIVE") . '/ecfr';
		$folder = 'xml/title-' . $titleNumber . "/partials/" . $versionDate;
		$part = 'part-' . $part;
		$filename = '_title-' . $titleNumber . '-' . $versionDate . '-' . $part . '.xml';
		$filepath = $storageDrive . '/' . $folder . '/' . $filename;

		if (file_exists($filepath . '.zip')) {
			return true;
		}
		return false;
	}
}

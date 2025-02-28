<?php

namespace App\Console\Commands;

use App\Models\Title;
use App\Models\TitleEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\FetchHistoricalDocumentJob;
use App\Jobs\FetchLargeDocumentIncrementJob;

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

		$titles = Title::all();
		foreach($titles as $title) {
			foreach($title->versionDates() as $date) {
				$formattedDate = $date->date->format('Y-m-d');

				// Title 40 is too large, we will have to handle this one separately
				if ((int) $title->number == 40) {

				}

				// if ($this->fileAlreadyDownloaded($title->number, $formattedDate)) {
				// 	continue;
				// }

				// FetchHistoricalDocumentJob::dispatch($title->number, $formattedDate);
			}
		}
    }

	private function fileAlreadyDownloaded($titleNumber, $versionDate) {
		$storageDrive = env("STORAGE_DRIVE");
		$folder = 'title-' . $titleNumber;
		$filename = 'title-' . $titleNumber . '-' . $versionDate . '.xml';
		$filepath = $storageDrive . '/' . $folder . '/' . $filename;
		if (file_exists($filepath . '.zip')) {
			return true;
		}
		return false;
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

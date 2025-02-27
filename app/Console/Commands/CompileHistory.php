<?php

namespace App\Console\Commands;

use App\Models\Title;
use Illuminate\Console\Command;
use App\Jobs\FetchHistoricalDocumentJob;
use Illuminate\Support\Facades\DB;

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
				FetchHistoricalDocumentJob::dispatch($title->number, $formattedDate);
			}
		}
    }
}

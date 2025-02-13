<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ECFRService;
use Illuminate\Support\Facades\Storage;

class DownloadECFRTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:titles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download eCFR titles from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ecfr = new ECFRService();

		// Download Titles
		$titles = $ecfr->fetchTitles();
		Storage::disk('local')->put('titles.json', json_encode($titles));
    }
}

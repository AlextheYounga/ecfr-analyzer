<?php

namespace App\Console\Commands\Download;

use Illuminate\Console\Command;

class DownloadECFR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call all download commands for eCFR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->call('ecfr:download-titles');
		$this->call('ecfr:download-versions');
		$this->call('ecfr:download-stuctures');
    }
}

<?php

namespace App\Console\Commands;

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
		$this->call('ecfr:titles');
		$this->call('ecfr:versions');
		$this->call('ecfr:stuctures');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GetAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download all content in order.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('ecfr:titles');
		$this->call('ecfr:entities');
		$this->call('ecfr:agencies');
		$this->call('ecfr:documents');
		$this->call('ecfr:content');
		$this->call('ecfr:agency-titles');
		$this->call('ecfr:agency-words');
    }
}

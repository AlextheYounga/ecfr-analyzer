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
		$this->info('Starting titles fetch...');
        $this->call('ecfr:titles');

		$this->info('Starting entities fetch...');
		$this->call('ecfr:entities');

		$this->info('Starting agencies fetch...');
		$this->call('ecfr:agencies');

		$this->info('Starting fetch documents jobs...');
		$this->call('ecfr:documents');

		$this->info('Starting content mapping...');
		$this->call('ecfr:content');

		$this->info('Starting agency titles mapping...');
		$this->call('ecfr:agency-titles');

		$this->info('Starting agency words mapping...');
		$this->call('ecfr:agency-words');
    }
}

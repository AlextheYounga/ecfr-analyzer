<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'ecfr:all {--fast}';

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
		$options = [];
		if ($this->option('fast')) {
			$options['--fast'] = true;
		}

		$this->info('Starting titles fetch...');
        $this->call('ecfr:titles');

		$this->info('Starting entities fetch...');
		$this->call('ecfr:entities', $options);

		$this->info('Starting versions fetch...');
		$this->call('ecfr:versions', $options);

		$this->info('Starting agencies fetch...');
		$this->call('ecfr:agencies');

		$this->info('Starting fetch documents jobs...');
		$this->call('ecfr:documents');

		$this->info('Starting fetch documents jobs...');
		$this->call('queue:work', [
			'--memory' => 2000,
			'--rest' => 0.5,
			'--stop-when-empty' => true,
		]);

		$this->info('Starting content mapping...');
		$this->call('ecfr:content', $options);

		$this->info('Starting agency titles mapping...');
		$this->call('ecfr:agency-titles');

		$this->info('Starting agency words mapping...');
		$this->call('ecfr:agency-words');
    }
}

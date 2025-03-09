<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Title;
use App\Jobs\FetchCurrentDocumentJob;


class GetTitleDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest title documents from ECFR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->call('queue:clear');
		$this->call('queue:flush');

        $titles = Title::all();
		if (!$titles) {
			throw new \Exception("No titles found. Run php artisan ecfr:titles");	
		}

		foreach ($titles as $title) {
			if ($title->reserved) {
				continue;
			}
			
			$this->info("Dispatching fetch document job for " . $title->number);
			FetchCurrentDocumentJob::dispatch($title);
		}
    }
}

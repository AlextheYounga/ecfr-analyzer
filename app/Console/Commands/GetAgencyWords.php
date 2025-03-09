<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class GetAgencyWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:agency-words';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the word count for each agency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agencies = Agency::all();
		foreach ($agencies as $agency) {
			$this->info("Calculating word count for " . $agency->id . " " . $agency->name);	
			$agency->word_count = $this->getWords($agency);
			$agency->save();
		}	
    }

	private function getWords($agency) {
		$wordsCountQuery = DB::table('agency_title_entity')
			->join('title_entities', 'agency_title_entity.title_entity_id', '=', 'title_entities.id')
			->join('title_contents', 'title_entities.id', '=', 'title_contents.title_entity_id')
			->where('agency_title_entity.agency_id', $agency->id)
			->where('title_entities.type', 'section')
			->sum('title_contents.word_count');
		return $wordsCountQuery;
	}
}

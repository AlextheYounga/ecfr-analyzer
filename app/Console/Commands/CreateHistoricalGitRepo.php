<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Version;
use App\Jobs\CommitToHistoricalGitRepoJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CreateHistoricalGitRepo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecfr:git';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to create historical git repos for each version';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->call('queue:clear');
		$this->call('queue:flush');

        $versions = Version::select('issue_date')
			->distinct()
			->orderBy('issue_date', 'asc')
			->get();


		$last_commit_version = $this->getLastCommitVersion();
		$last_commit_date = Carbon::parse($last_commit_version);

		foreach ($versions as $version) {
			$versionDate = Carbon::parse($version->issue_date);
			if ($last_commit_date->gte($versionDate)) {
				continue;
			}

			$this->info('Dispatching job for ' . $version->issue_date);
			CommitToHistoricalGitRepoJob::dispatch($version->issue_date->format('Y-m-d'));
		}
    }

	private function getLastCommitVersion() {
		$git_repo = env('GIT_REPO');

		$last_commit_message = shell_exec("cd $git_repo && git log -1");
		$needle = 'release(cfr):';
		if (str_contains($last_commit_message, $needle)) {
			$first_chunk = explode($needle, $last_commit_message)[1];
			$second_chunk = trim(explode("\n", $first_chunk)[0]);
			$issue_date = explode(' ', $second_chunk)[0];
			return $issue_date;
		}
		return null;
	}
}

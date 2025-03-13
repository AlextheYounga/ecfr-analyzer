<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class CommitToHistoricalGitRepoJob implements ShouldQueue
{
    use Queueable;


	public $version;

	// Set the number of times the job may be attempted.
	public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

	/**
	 * Get the middleware the job should pass through.
	 *
	 * @return array<int, object>
	 */
	public function middleware(): array
	{
		return [new WithoutOverlapping($this->version)];
	}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
		$this->refreshFolders();
		
		$shell_command = "./scripts/create_historical_git";
		$working_folder = Storage::disk('local')->path('ecfr');
		$historical_folder = Storage::disk('storage_drive')->path('ecfr/historical');
		
		$command_inputs = [$shell_command, $this->version, $working_folder, $historical_folder, "2>&1"];
		$command = implode(" ", array_map('escapeshellarg', $command_inputs)); // Escape arguments properly
		
		// Use passthru() for real-time output
		passthru($command, $exit_code);
		
		if ($exit_code !== 0) {
			throw new \Exception("Failed to create historical git repo for $this->version. Exit code: $exit_code");
		}
    }

	private function refreshFolders() {
		Storage::disk('local')->deleteDirectory('ecfr');
		Storage::disk('local')->makeDirectory('ecfr');
		Storage::disk('local')->makeDirectory('ecfr/xml');
		Storage::disk('local')->makeDirectory('ecfr/structure');
	}

}

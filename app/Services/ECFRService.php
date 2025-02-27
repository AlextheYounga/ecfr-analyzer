<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ECFRService
{
	public $apiRoot = 'https://www.ecfr.gov/api/';

	public function fetchTitles() {
		$apiUrl = $this->apiRoot . 'versioner/v1/titles.json';
		$response = Http::timeout(60)->get($apiUrl);
		if ($response->failed()) {
			throw new \Exception("Failed to fetch document");
		}
		return \json_decode($response->body(), true);
	}

	public function fetchVersions($titleNumber) {
		$apiUrl = $this->apiRoot . 'versioner/v1/versions/title-' . (string) $titleNumber . '.json';
		try {
			$response = Http::timeout(60)->get($apiUrl);
			if ($response->failed()) {
				throw new \Exception("Failed to fetch document");
			}
			return \json_decode($response->body(), true);
		} catch (\Exception $e) {
			echo "Error fetching versions for title " . $titleNumber . "\n";
			echo $e->getMessage() . "\n";
		}
	}

	public function fetchStructure($titleNumber, $versionDate) {
		$apiUrl = $this->apiRoot . 'versioner/v1/structure/'. $versionDate . '/title-' . $titleNumber . '.json';
		try {
			$response = Http::timeout(60)->get($apiUrl);
			if ($response->failed()) {
				throw new \Exception("Failed to fetch document");
			}
			return \json_decode($response->body(), true);
		} catch (\Exception $e) {
			echo "Error fetching structure for title " . $titleNumber . "\n";
			echo $e->getMessage() . "\n";
		}
	}

	public function fetchDocument($titleNumber, $versionDate) {
		$apiUrl = $this->apiRoot . 'versioner/v1/full/' . $versionDate . '/title-' . $titleNumber . '.xml';
		echo "Fetching " . $apiUrl . "\n";
		try {
			$response = Http::timeout(600)->get($apiUrl);
			if ($response->failed()) {
				throw new \Exception("Failed to fetch document: " . $response->status() . " " . $response->body());
			}
			return $response->body();
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage()
			];
		}
	}

	public function fetchAgencies() {
		$apiUrl = $this->apiRoot . 'admin/v1/agencies.json';
		$response = Http::timeout(60)->get($apiUrl);
		if ($response->failed()) {
			throw new \Exception("Failed to fetch document");
		}
		return \json_decode($response->body(), true);
	}
}

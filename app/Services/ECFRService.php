<?php

namespace App\Services;

// use Illuminate\Support\Facades\Http;
class ECFRService
{
	public $apiRoot = 'https://www.ecfr.gov/api/';

	public function fetchTitles() {
		$apiUrl = $this->apiRoot . 'versioner/v1/titles.json';
		$titles = file_get_contents($apiUrl);
		return \json_decode($titles, true);
	}

	public function fetchVersion($titleNumber) {
		$apiUrl = $this->apiRoot . 'versioner/v1/versions/title-' . (string) $titleNumber . '.json';
		try {
			$versions = file_get_contents($apiUrl);
			return \json_decode($versions, true);
		} catch (\Exception $e) {
			echo "Error fetching versions for title " . $titleNumber . "\n";
			echo $e->getMessage() . "\n";
		}
	}

	public function fetchStructure($titleNumber, $versionDate) {
		$apiUrl = $this->apiRoot . 'versioner/v1/structure/'. $versionDate . '/title-' . $titleNumber . '.json';
		try {
			$structure = file_get_contents($apiUrl);
			return \json_decode($structure, true);
		} catch (\Exception $e) {
			echo "Error fetching structure for title " . $titleNumber . "\n";
			echo $e->getMessage() . "\n";
		}
	}

	public function fetchDocuments() {
		//
	}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class Structure extends Model
{
    
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'title_id',
		'date',
		'identifier',
		'label',
		'label_level',
		'label_description',
		'size',
		'children',
	];

	/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
			'children' => 'blob'
        ];
    }

	/**
	 * The attributes that should be appended to the model's array form.
	 *
	 * @var array<int, string>
	 */
    protected $appends = [
        'children'
    ];

	/**
     * Upon fetching the model, get the children from the JSON file and append to object.
     *
     * @return array<string, string>
     */
	public function getChildrenAttribute()
    {
		$fileReference = $this->structure_reference;
		if (empty($fileReference)) {
			return [];
		}
		$structureZipFile = storage_path('app/private/' . $fileReference . '.zip');
		$structure = $this->readZipFile($structureZipFile);
		$children = $structure['children'] ?? [];
		return $children;
    }

	public function title() {
		return $this->belongsTo(Title::class);
	}

	private function readZipFile($zipFile) {
		$zip = new ZipArchive;
		if ($zip->open($zipFile) === TRUE) {
			$fileName = $zip->getNameIndex(0); // first file inside zip
			$fileContent = $zip->getFromName($fileName);
			$zip->close();
		
			$jsonData = json_decode($fileContent, true);
			if ($jsonData !== null) {
				return $jsonData;
			} else {
				return [];
			}
		} else {
			return [];
		}
	}
}

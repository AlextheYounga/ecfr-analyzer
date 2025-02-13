<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class TitleStructure extends Model
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
		'structure_reference',
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
		$structureJsonStorage = Storage::disk('local')->get($fileReference);
		$structure = json_decode($structureJsonStorage, true);
		$children = $structure['children'] ?? [];
		return $children;
    }

	public function title() {
		return $this->belongsTo(Title::class);
	}
}

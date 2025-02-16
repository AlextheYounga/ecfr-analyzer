<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Title extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'number',
		'name',
		'latest_amended_on',
		'latest_issue_date',
		'up_to_date_as_of',
		'reserved',
		'structure_reference',
		'word_count',
		'properties',
    ];

	/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latest_amended_on' => 'date',
            'latest_issue_date' => 'date',
			'up_to_date_as_of' => 'date',
			'properties' => 'json',
        ];
    }

	/**
     * Get the title structure from the JSON file
     *
     * @return array<string, string>
     */
	public function getStructure()
    {
		$fileReference = $this->structure_reference;
		if (empty($fileReference)) {
			return [];
		}
		$structure = file_get_contents($fileReference);
		return json_decode($structure, true);
    }

	public function agencies()
    {
        return $this->belongsToMany(Agency::class);
    }
}

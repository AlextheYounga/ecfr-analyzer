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
		'structure'
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
			'structure' => 'json'
        ];
    }

	public function versions() {
		return $this->hasMany(Version::class);
	}

	public function latestVersion()
	{
		return $this->versions()
			->orderByDesc('date')
			->first();
	}
}

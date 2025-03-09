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
	 * The attributes that should be appended to the model.
	 *
	 * @var array<int, string>
	 */
	protected $appends = ['large'];

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

	public function getLargeAttribute() {
		// Currently only Title 40 is too large to download in one go.
		return $this->entities()->first()->size > 100000000; // 100MB
	}

	public function agencies()
    {
        return $this->belongsToMany(Agency::class);
    }

	public function entities() {
		return $this->hasMany(TitleEntity::class);
	}

	public function versions() {
		return $this->hasMany(Version::class);
	}

	public function versionDates() {
		return $this->versions()->select('issue_date')->distinct()->get();
	}

}

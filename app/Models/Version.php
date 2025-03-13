<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
		'date',
		'amendment_date',
		'issue_date',
		'title',
		'type',
		'identifier',
		'name',
		'part',
		'substantive',
		'removed',
		'subpart',
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
            'amendment_date' => 'date',
			'issue_date' => 'date',
        ];
    }

	public function title() {
		return $this->belongsTo(Title::class);
	}

	public function titleEntity() {
		return $this->belongsTo(TitleEntity::class);
	}
}

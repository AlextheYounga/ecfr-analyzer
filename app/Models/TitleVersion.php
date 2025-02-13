<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitleVersion extends Model
{
    /**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'title_id',
		'date',
		'amendment_date',
		'issue_date',
		'identifier',
		'name',
		'part',
		'substantive',
		'removed',
		'subpart',
		'title',
		'type',
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
			'children' => 'json',
        ];
    }

	public function title() {
		return $this->belongsTo(Title::class);
	}	
}

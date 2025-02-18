<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Agency extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'parent_id',
		'name',
		'short_name',
		'display_name',
		'sortable_name',
		'slug',
		'children',
		'cfr_references',
    ];

	/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
			'children' => 'json',
			'cfr_references' => 'json'
        ];
    }

	public function parent() {
		return $this->belongsTo(Agency::class, 'parent_id');
	}

	public function children() {
		return $this->hasMany(Agency::class, 'parent_id', 'id');
	}

	public function entities()
	{
		return $this->belongsToMany(TitleEntity::class);
	}

	public function getWords() {
		$wordsCountQuery = DB::table('agency_title_entity')
			->join('title_entities', 'agency_title_entity.title_entity_id', '=', 'title_entities.id')
			->join('title_contents', 'title_entities.id', '=', 'title_contents.title_entity_id')
			->where('agency_title_entity.agency_id', $this->id)
			->where('title_entities.type', 'section')
			->sum('title_contents.word_count');
		return $wordsCountQuery;
	}
}

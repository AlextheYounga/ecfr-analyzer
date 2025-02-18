<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class TitleEntity extends Model
{

	protected $table = 'title_entities';	

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'title_id',
		'parent_id',
		'level',
		'order_index',
		'identifier',
		'label',
		'label_level',
		'label_description',
		'reserved',
		'type',
		'size',
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
			'properties' => 'json',
        ];
    }

	public function title() {
		return $this->belongsTo(Title::class);
	}

	public function children() {
		return $this->hasMany(TitleEntity::class, 'parent_id', 'id');
	}

	public function agencies() {
		return $this->belongsToMany(Agency::class);
	}

	public function content() {
		return $this->hasOne(TitleContent::class);
	}

	public function getAllChildren()
	{
		$query = "
			WITH RECURSIVE children_cte AS (
				-- Base case: start with the selected item
				SELECT id, title_id, parent_id, identifier, type
				FROM title_entities
				WHERE id = :starting_id
	
				UNION ALL
	
				-- Recursive step: select children of the items in the CTE
				SELECT te.id, te.title_id, te.parent_id, te.identifier, te.type
				FROM title_entities te
				INNER JOIN children_cte cte ON te.parent_id = cte.id
			)
			SELECT * FROM children_cte;
		";
	
		return DB::select($query, ['starting_id' => $this->id]);
	}
}

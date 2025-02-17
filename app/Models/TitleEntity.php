<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

	public function content() {
		return $this->hasOne(TitleContent::class, 'entity_id', 'id');
	}

}

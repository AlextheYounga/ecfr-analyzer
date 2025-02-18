<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitleContent extends Model
{
	protected $table = 'title_contents';	

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'title_id',
		'title_entity_id',
		'content',
		'word_count',
    ];


	public function title() {
		return $this->belongsTo(Title::class);
	}

	public function entity() {
		return $this->belongsTo(TitleEntity::class, 'title_entity_id');
	}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
		return $this->hasMany(Agency::class, 'parent_id');
	}
}

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
		'name',
		'short_name',
		'display_name',
		'sortable_name',
		'slug',
		'children',
		'cfr_references',
    ];
}

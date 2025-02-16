<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Gitonomy\Git\Repository;

use App\Models\Agency;
use Inertia\Inertia;
use App\Models\Title;


class PageController extends Controller
{
    public function welcome() {
		return Inertia::render('Welcome', [
				'titles' => Title::orderBy('word_count', 'desc')->get(),
				'totalWords' => Title::sum('word_count'),
			]
		);
	}
}

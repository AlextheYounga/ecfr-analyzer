<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Gitonomy\Git\Repository;
use App\Models\Agency;
use Inertia\Inertia;
use App\Models\Title;



class DashboardController extends Controller
{
    public function welcome() {
		return Inertia::render('Welcome', [
				'agencyCount' => Agency::count(),
				'agencies' => Agency::where('word_count', '>', 0)	
					->orderBy('word_count', 'desc')
					->get(),
				'titles' => Title::where('reserved', false)
					->orderBy('word_count', 'desc')
					->get(),
				'totalWords' => Title::sum('word_count'),
			]
		);
	}
}

<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use Inertia\Inertia;
use Gitonomy\Git\Repository;

class PageController extends Controller
{
    public function welcome() {

		$repository = new Repository(\storage_path('app/usc/.git'));
		dd($repository->getLog()->getCommits());
		return Inertia::render('Welcome', [
			'commits' => $repository->getLog()->getCommits(),
		]);
	}
}

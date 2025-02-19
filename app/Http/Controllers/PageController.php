<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agency;
use Inertia\Inertia;
use App\Models\Title;
use App\Models\TitleContent;
use App\Models\TitleEntity;
use Parsedown;
// use Gitonomy\Git\Repository;

class PageController extends Controller
{
    public function dashboard() {
		$agencies = Agency::where('word_count', '>', 0)	
			->orderBy('word_count', 'desc')
			->get();

		$titles = Title::where('reserved', false)
			->orderBy('word_count', 'desc')
			->get();

		return Inertia::render('Dashboard', [
				'agencyCount' => Agency::count(),
				'agencies' => $agencies,
				'titles' => $titles,
				'totalWords' => Title::sum('word_count'),
			]
		);
	}

	public function titles() {
		$titles = TitleEntity::where('type', 'title')
			->select('id', 'title_id', 'label', 'type')
			->get();

		return Inertia::render('Titles', [
				'titles' => $titles,
			]
		);
	}

 	public function sections(Request $request) {
		$id = $request->id;
		$section = TitleEntity::find($id);

		if ($section->type != 'section') {
			return redirect()->route('titles');
		}
		$section->parents = $section->getAllParents();

		$parser = new Parsedown();
		$content = $section->content()->first();
		$html = $parser->text($content->content);
		$section->content = !empty($html) ? $html : '';
		
		return Inertia::render('Section', [
				'section' => $section,
			]
		);
	}

	// Ajax call
	public function children($id) {
		$title = TitleEntity::find($id);
		$children = $title->children()
			->select('id', 'title_id', 'label', 'type')
			->get();

		return response()->json($children);
	}

	public function search(Request $request) {
		$search = $request->search;
		$parser = new Parsedown();
		$results = TitleContent::where('content', 'like', "%$search%")->take(30)->get();
		$results = $results->map(function($result) use($parser) {
			$result->content = strip_tags($parser->text($result->content));
			$result->entity = $result->entity()->first();
			$result->parents = $result->entity->getAllParents();
			return $result;
		});

		return Inertia::render('Search', [
			'results' => $results,
		]);
	}
}

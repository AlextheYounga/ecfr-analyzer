<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Title;
use Illuminate\Support\Facades\Storage;

class TitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
		Title::truncate();
		$jsonStorage = Storage::disk('local')->get('titles.json');
		$titles = json_decode($jsonStorage, true);

		foreach($titles['titles'] as $title) {
			Title::create($title);
		}
    }
}

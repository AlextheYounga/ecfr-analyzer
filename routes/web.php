<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/titles', [PageController::class, 'titles'])->name('titles');
Route::get('/sections/{id}', [PageController::class, 'sections'])->name('sections');

Route::get('/entities/{id}/children', [PageController::class, 'children'])
    ->name('documents.children');

Route::get('/search', [PageController::class, 'search'])
    ->name('documents.search');

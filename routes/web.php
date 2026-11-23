<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Site\ApplicationController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\StorySubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('litters', [SiteController::class, 'litters'])->name('site.litters');
Route::get('our-dogs', [SiteController::class, 'dogs'])->name('site.dogs');
Route::get('our-dogs/{dog}', [SiteController::class, 'dog'])->name('site.dog');
Route::get('gallery', [SiteController::class, 'gallery'])->name('site.gallery');
Route::redirect('happy-families', '/#happy-families', 301);
Route::get('share-your-story', [StorySubmissionController::class, 'create'])->name('site.story');
Route::post('share-your-story', [StorySubmissionController::class, 'store'])->middleware('throttle:3,10')->name('site.story.store');
Route::redirect('how-it-works', '/contact#how-it-works', 301);
Route::redirect('health-testing', '/our-dogs', 301);
Route::get('privacy', [SiteController::class, 'privacy'])->name('site.privacy');
Route::get('contact', [SiteController::class, 'contact'])->name('site.contact');
Route::get('apply', [ApplicationController::class, 'create'])->name('site.apply');
Route::post('apply', [ApplicationController::class, 'store'])->middleware('throttle:5,10')->name('site.apply.store');
Route::get('sitemap.xml', [SiteController::class, 'sitemap']);

Route::get('admin', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

require __DIR__.'/admin.php';

require __DIR__.'/settings.php';

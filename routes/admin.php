<?php

use App\Http\Controllers\Admin\AlbumController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContactNoteController;
use App\Http\Controllers\Admin\DiaryEntryController;
use App\Http\Controllers\Admin\DogController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HealthTestController;
use App\Http\Controllers\Admin\HeatCycleController;
use App\Http\Controllers\Admin\LitterController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\PuppyController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\WaitlistController;
use App\Http\Controllers\Admin\WeighInController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('dogs', DogController::class)->except('show');
    Route::post('dogs/{dog}/health-tests', [HealthTestController::class, 'store'])->name('health-tests.store');
    Route::put('health-tests/{healthTest}', [HealthTestController::class, 'update'])->name('health-tests.update');
    Route::delete('health-tests/{healthTest}', [HealthTestController::class, 'destroy'])->name('health-tests.destroy');

    Route::post('dogs/{dog}/heat-cycles', [HeatCycleController::class, 'store'])->name('heat-cycles.store');
    Route::delete('heat-cycles/{heatCycle}', [HeatCycleController::class, 'destroy'])->name('heat-cycles.destroy');

    Route::resource('litters', LitterController::class)->except('show');
    Route::post('litters/{litter}/diary', [DiaryEntryController::class, 'store'])->name('diary.store');
    Route::delete('diary/{diaryEntry}', [DiaryEntryController::class, 'destroy'])->name('diary.destroy');
    Route::post('litters/{litter}/weigh-ins', [WeighInController::class, 'store'])->name('weigh-ins.store');
    Route::delete('litters/{litter}/weigh-ins/{date}', [WeighInController::class, 'destroy'])->name('weigh-ins.destroy');
    Route::post('litters/{litter}/puppies', [PuppyController::class, 'store'])->name('puppies.store');
    Route::put('puppies/{puppy}', [PuppyController::class, 'update'])->name('puppies.update');
    Route::delete('puppies/{puppy}', [PuppyController::class, 'destroy'])->name('puppies.destroy');

    Route::resource('contacts', ContactController::class)->except('show');
    Route::post('contacts/{contact}/notes', [ContactNoteController::class, 'store'])->name('contact-notes.store');
    Route::delete('contact-notes/{contactNote}', [ContactNoteController::class, 'destroy'])->name('contact-notes.destroy');

    Route::resource('waitlist', WaitlistController::class)->except('show')->parameters(['waitlist' => 'waitlist']);

    Route::resource('testimonials', TestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('export/{type}', ExportController::class)->name('export');

    Route::get('site', [SiteSettingsController::class, 'edit'])->name('site.edit');
    Route::put('site', [SiteSettingsController::class, 'update'])->name('site.update');

    Route::inertia('help', 'admin/Help')->name('help');

    Route::post('photos/bulk', [PhotoController::class, 'bulk'])->name('photos.bulk');
    Route::resource('photos', PhotoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('albums', AlbumController::class)->only(['store', 'update', 'destroy']);
});

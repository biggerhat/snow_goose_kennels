<?php

use App\Http\Controllers\Admin\AkcApplicationController;
use App\Http\Controllers\Admin\AlbumController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContactNoteController;
use App\Http\Controllers\Admin\DiaryEntryController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DogAchievementController;
use App\Http\Controllers\Admin\DogController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HealthRecordController;
use App\Http\Controllers\Admin\HealthTestController;
use App\Http\Controllers\Admin\HeatCycleController;
use App\Http\Controllers\Admin\LitterController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\PuppyController;
use App\Http\Controllers\Admin\RecordsController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\WaitlistController;
use App\Http\Controllers\Admin\WeighInController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('dogs/quick', [DogController::class, 'quick'])->name('dogs.quick');
    Route::resource('dogs', DogController::class)->except('show')->withTrashed(['edit', 'update']);
    Route::post('dogs/{dog}/restore', [DogController::class, 'restore'])->withTrashed()->name('dogs.restore');
    Route::delete('dogs/{dog}/purge', [DogController::class, 'purge'])->withTrashed()->name('dogs.purge');
    Route::post('dogs/{dog}/health-tests', [HealthTestController::class, 'store'])->name('health-tests.store');
    Route::put('health-tests/{healthTest}', [HealthTestController::class, 'update'])->name('health-tests.update');
    Route::delete('health-tests/{healthTest}', [HealthTestController::class, 'destroy'])->name('health-tests.destroy');

    Route::post('dogs/{dog}/achievements', [DogAchievementController::class, 'store'])->name('achievements.store');
    Route::put('achievements/{achievement}', [DogAchievementController::class, 'update'])->name('achievements.update');
    Route::delete('achievements/{achievement}', [DogAchievementController::class, 'destroy'])->name('achievements.destroy');
    Route::post('dogs/{dog}/heat-cycles', [HeatCycleController::class, 'store'])->name('heat-cycles.store');
    Route::delete('heat-cycles/{heatCycle}', [HeatCycleController::class, 'destroy'])->name('heat-cycles.destroy');

    Route::resource('litters', LitterController::class)->except('show')->withTrashed(['edit', 'update']);
    Route::get('litters/{litter}/akc-application', [AkcApplicationController::class, 'show'])->withTrashed()->name('litters.akc');
    Route::get('akc/litter-application.pdf', [AkcApplicationController::class, 'form'])->name('akc.litter-form');
    Route::post('litters/{litter}/restore', [LitterController::class, 'restore'])->withTrashed()->name('litters.restore');
    Route::delete('litters/{litter}/purge', [LitterController::class, 'purge'])->withTrashed()->name('litters.purge');
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

    Route::get('records', [RecordsController::class, 'index'])->name('records');
    Route::get('records/emergency-plan', [RecordsController::class, 'emergencyPlan'])->name('records.emergency-plan');
    Route::get('records/all', [RecordsController::class, 'all'])->name('records.all');
    Route::get('records/dogs/{dog}', [RecordsController::class, 'dog'])->withTrashed()->name('records.dog');
    Route::get('records/litters/{litter}', [RecordsController::class, 'litter'])->withTrashed()->name('records.litter');

    Route::post('photos/bulk', [PhotoController::class, 'bulk'])->name('photos.bulk');
    Route::resource('photos', PhotoController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('albums', AlbumController::class)->only(['store', 'update', 'destroy']);
    Route::post('dogs/{dog}/health-records', [HealthRecordController::class, 'storeForDog'])->name('health-records.dog');
    Route::post('puppies/{puppy}/health-records', [HealthRecordController::class, 'storeForPuppy'])->name('health-records.puppy');
    Route::post('litters/{litter}/health-records', [HealthRecordController::class, 'storeForLitter'])->name('health-records.litter');
    Route::put('health-records/{health_record}', [HealthRecordController::class, 'update'])->name('health-records.update');
    Route::delete('health-records/{health_record}', [HealthRecordController::class, 'destroy'])->name('health-records.destroy');
    Route::resource('documents', DocumentController::class)->only(['store', 'update', 'show', 'destroy']);
});

<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Photo;
use App\Services\ImageStore;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/** Sorts the demo photos into private albums and adds a few photos that are not on the website. */
class DemoAlbumsSeeder extends Seeder
{
    public function run(): void
    {
        if (Album::exists()) {
            return;
        }

        $portraits = Album::create(['name' => 'Dog portraits', 'description' => 'Best headshots of the adults.', 'sort_order' => 1]);
        $litter = Album::create(['name' => 'Honey × Tucker litter', 'description' => 'Whelping through go-home.', 'sort_order' => 2]);
        $winter = Album::create(['name' => 'Winter 2026', 'sort_order' => 3]);
        $vet = Album::create(['name' => 'Vet visits', 'description' => 'Records and checkups. Private.', 'sort_order' => 4]);
        Album::create(['name' => 'Ideas for next year', 'description' => 'Pairings and puppy buyers to remember.', 'sort_order' => 5]);

        Photo::where('photoable_type', 'dog')->update(['album_id' => $portraits->id]);
        Photo::where('photoable_type', 'litter')->update(['album_id' => $litter->id]);
        Photo::where('caption', 'like', '%snow%')->update(['album_id' => $winter->id]);

        // Keep a few out of the public eye.
        Photo::where('caption', 'like', '%fire%')->update(['is_public' => false, 'in_gallery' => false]);

        foreach (['yellow-a13' => 'Tucker, weigh-in day', 'choc-02' => 'Maple, after her check', 'black-hunting' => 'Ranger, vet exam'] as $file => $caption) {
            $path = database_path("seeders/demo-photos/$file.jpg");
            $stored = app(ImageStore::class)->store(new UploadedFile($path, "$file.jpg", 'image/jpeg', null, true));
            Photo::create($stored + [
                'album_id' => $vet->id, 'caption' => $caption, 'taken_on' => now()->subDays(random_int(10, 90)),
                'is_public' => false, 'in_gallery' => false,
            ]);
        }

        foreach (['pup-1', 'lab-p1'] as $file) {
            $path = database_path("seeders/demo-photos/$file.jpg");
            $stored = app(ImageStore::class)->store(new UploadedFile($path, "$file.jpg", 'image/jpeg', null, true));
            Photo::create($stored + ['credit' => 'Wikimedia Commons (CC0/PD)', 'is_public' => false, 'in_gallery' => false]);
        }
    }
}

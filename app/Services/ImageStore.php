<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

class ImageStore
{
    /** Whether this server's ImageMagick can read iPhone HEIC photos. */
    public static function supportsHeic(): bool
    {
        return in_array('HEIC', Imagick::queryFormats('HEIC'), true);
    }

    /**
     * Resize, auto-rotate and strip metadata (including GPS) from an upload.
     *
     * @return array{path: string, thumb_path: string}
     */
    public function store(UploadedFile $file): array
    {
        $dir = 'photos/'.now()->format('Y/m');
        $name = Str::uuid()->toString();

        $full = $this->render($file->getRealPath(), 2000);
        $thumb = $this->render($file->getRealPath(), 640);

        $disk = Storage::disk(config('kennel.photo_disk'));
        $disk->put("$dir/$name.jpg", $full);
        $disk->put("$dir/{$name}_thumb.jpg", $thumb);

        return ['path' => "$dir/$name.jpg", 'thumb_path' => "$dir/{$name}_thumb.jpg"];
    }

    public function delete(string ...$paths): void
    {
        Storage::disk(config('kennel.photo_disk'))->delete($paths);
    }

    private function render(string $source, int $max): string
    {
        $img = new Imagick($source.'[0]');
        $img->autoOrient();
        $img->setImageBackgroundColor('white');
        $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $img->stripImage();

        if ($img->getImageWidth() > $max || $img->getImageHeight() > $max) {
            $img->thumbnailImage($max, $max, true);
        }

        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(84);
        $img->setInterlaceScheme(Imagick::INTERLACE_PLANE);

        $blob = $img->getImageBlob();
        $img->clear();

        return $blob;
    }
}

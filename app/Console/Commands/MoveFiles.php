<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Copies photos and documents from this server's folders to the disks set in .env (for example Backblaze). */
class MoveFiles extends Command
{
    protected $signature = 'kennel:move-files {--delete-source : Remove each file from the old location once it is safely copied}';

    protected $description = 'Copy existing photos and documents to the storage set in PHOTO_DISK and DOCUMENT_DISK. Safe to run again.';

    public function handle(): int
    {
        $failed = 0;

        $failed += $this->move('Photos', 'public', (string) config('kennel.photo_disk'), Photo::query()->get(['path', 'thumb_path'])->flatMap(fn ($p) => [$p->path, $p->thumb_path])->filter()->unique()->all());
        $failed += $this->move('Documents', 'local', (string) config('kennel.document_disk'), Document::query()->pluck('path')->filter()->unique()->all());

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @param  list<string>  $paths */
    private function move(string $label, string $from, string $to, array $paths): int
    {
        if ($from === $to) {
            $this->line("$label: already on the '$to' disk, nothing to move.");

            return 0;
        }

        $source = Storage::disk($from);
        $target = Storage::disk($to);
        [$copied, $had, $missing, $failed] = [0, 0, 0, 0];

        foreach ($paths as $path) {
            if ($target->exists($path)) {
                $had++;
            } elseif (! $source->exists($path)) {
                $missing++;
            } else {
                $stream = $source->readStream($path);

                if ($stream && $target->writeStream($path, $stream) && $target->exists($path)) {
                    $copied++;
                } else {
                    $failed++;
                    $this->error("Could not copy $path");

                    continue;
                }

                is_resource($stream) && fclose($stream);
            }

            if ($this->option('delete-source') && $target->exists($path) && $source->exists($path)) {
                $source->delete($path);
            }
        }

        $this->info("$label: copied $copied, already there $had, missing from the old folder $missing, failed $failed.");

        return $failed;
    }
}

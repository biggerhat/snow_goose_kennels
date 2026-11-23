<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Services\ImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** One-stop "is this server set up right for the kennel site?" report. */
class CheckSetup extends Command
{
    protected $signature = 'kennel:check';

    protected $description = 'Check this server is ready: uploads, photos, mail, scheduler, owner login and production settings.';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->add(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP version', PHP_VERSION, 'Needs PHP 8.3 or newer.');
        $this->add(extension_loaded('imagick'), 'Image library (Imagick)', 'installed', 'Install php-imagick. Photo uploads will not work without it.');

        if (extension_loaded('imagick')) {
            $this->rows[] = ['info', 'iPhone HEIC photos', ImageStore::supportsHeic() ? 'supported' : 'not supported: iPhone users should upload JPEG (Settings > Camera > Formats > Most Compatible)'];
        }

        $this->limit('upload_max_filesize', 10, 'Phone photos are often 3 to 8 MB. Set upload_max_filesize to 25M.');
        $this->limit('post_max_size', 30, 'Set post_max_size to at least 60M so several photos can upload together.');
        $this->limit('memory_limit', 256, 'Set memory_limit to 512M so large photos can be resized.');

        $this->storage('Photo storage', (string) config('kennel.photo_disk'));
        $this->storage('Document storage', (string) config('kennel.document_disk'));
        $this->add(is_writable(base_path('bootstrap/cache')), 'Cache folder writable', 'bootstrap/cache', 'Make bootstrap/cache writable by the web server user.');

        if (config('database.default') === 'sqlite') {
            $db = config('database.connections.sqlite.database');
            $this->add(is_file($db) && is_writable($db) && is_writable(dirname($db)), 'Database file writable', $db, 'The web server user must be able to write the .sqlite file and its folder.');
        }

        $this->add(app()->environment('production'), 'APP_ENV', config('app.env'), 'Set APP_ENV=production on the live server.', warn: true);
        $this->add(! config('app.debug'), 'APP_DEBUG', config('app.debug') ? 'on' : 'off', 'Set APP_DEBUG=false on the live server; debug pages expose secrets.', warn: true);
        $this->add(! str_contains((string) config('app.url'), 'localhost'), 'APP_URL', (string) config('app.url'), 'Set APP_URL to the real https address.', warn: true);
        $this->add(config('app.timezone') !== 'UTC', 'Timezone', config('app.timezone'), 'Set APP_TIMEZONE to her local zone (for example America/Boise) so dates and reminders match her day.', warn: true);
        $this->add(! in_array(config('mail.default'), ['log', 'array'], true), 'Mail', config('mail.default'), 'Mail is not really sending. Application alerts and reminders only go to the log. Set MAIL_* in .env.', warn: true);

        $this->add(User::exists(), 'Owner login', User::count().' user(s)', 'Create one: php artisan kennel:owner "Her Name" her@email.com');
        $this->add(User::where('email', 'test@example.com')->doesntExist(), 'Demo user removed', 'no test@example.com', 'Delete the demo user test@example.com (password "password") before going live.');

        $beat = Cache::get('kennel.heartbeat');
        $fresh = $beat && now()->diffInMinutes($beat) < 10;
        $this->add($fresh, 'Scheduler (cron)', $beat ? "last ran {$beat}" : 'never ran', 'Add the cron line: * * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1', warn: true);

        $contact = Setting::contact();
        $this->add(filled($contact['phone'] ?? null) || filled($contact['email'] ?? null), 'Contact details', 'set', 'Fill in phone or email on the Website text page.', warn: true);

        $this->table(['', 'Check', 'Result'], array_map(fn ($r) => [match ($r[0]) {
            'ok' => '<fg=green>OK</>', 'warn' => '<fg=yellow>WARN</>', 'fail' => '<fg=red>FAIL</>', default => 'info',
        }, $r[1], $r[2]], $this->rows));

        $fails = collect($this->rows)->where(0, 'fail');

        foreach (collect($this->rows)->whereIn(0, ['fail', 'warn']) as $r) {
            $this->line(($r[0] === 'fail' ? '<fg=red>FIX</>' : '<fg=yellow>TIP</>')." {$r[1]}: {$r[3]}");
        }

        $fails->isEmpty() ? $this->info('Ready.') : $this->error($fails->count().' problem(s) to fix.');

        return $fails->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    /** Local folders must be writable; a Backblaze bucket must accept a small test file. */
    private function storage(string $label, string $disk): void
    {
        $driver = config("filesystems.disks.$disk.driver");

        if ($driver === 's3') {
            $why = null;

            try {
                // Built with errors switched on, so Backblaze's own message comes back instead of a silent false.
                $fs = Storage::build(['throw' => true] + (array) config("filesystems.disks.$disk"));
                $fs->put('.kennel-check', 'ok');
                $works = $fs->get('.kennel-check') === 'ok';
                $fs->delete('.kennel-check');
            } catch (\Throwable $e) {
                $works = false;
                $why = trim(preg_replace('/\s+/', ' ', $e->getPrevious()?->getMessage() ?: $e->getMessage()));
            }

            $this->add($works, $label, "$disk (Backblaze B2)", "Could not write to the $disk bucket".($why ? ': '.mb_strimwidth($why, 0, 300, '…') : '.').' Check B2_KEY_ID, B2_APPLICATION_KEY, B2_ENDPOINT and the bucket name in .env, and that the key can read and write that bucket.');

            return;
        }

        if ($disk === 'public') {
            $this->add(is_link(public_path('storage')) || is_dir(public_path('storage')), 'Public photo link', 'public/storage exists', 'Run: php artisan storage:link');
        }

        $root = (string) config("filesystems.disks.$disk.root");
        $this->add(is_dir($root) && is_writable($root) || is_writable(dirname($root)), $label, $root, 'Make storage/ writable by the web server user.');
    }

    private function add(bool $ok, string $label, string $result, string $fix, bool $warn = false): void
    {
        $this->rows[] = [$ok ? 'ok' : ($warn ? 'warn' : 'fail'), $label, $result, $fix];
    }

    private function limit(string $key, int $minMb, string $fix): void
    {
        $raw = (string) ini_get($key);
        $mb = $raw === '-1' ? PHP_INT_MAX : $this->toMb($raw);

        $this->add($mb >= $minMb, "PHP {$key}", $raw, $fix, warn: true);
    }

    private function toMb(string $v): float
    {
        $n = (float) $v;

        return match (strtolower(substr(trim($v), -1))) {
            'g' => $n * 1024,
            'k' => $n / 1024,
            'm' => $n,
            default => $n / 1048576,
        };
    }
}

<?php

/*
 * Keeps the admin help in step with the pages. Adding an admin page without a help panel,
 * or linking to a Help entry that does not exist, fails here (see the keep-admin-help-in-sync note).
 */

function adminPages(): array
{
    $dir = new RecursiveDirectoryIterator(resource_path('js/pages/admin'), FilesystemIterator::SKIP_DOTS);

    return collect(iterator_to_array(new RecursiveIteratorIterator($dir)))
        ->map(fn ($f) => $f->getPathname())
        ->filter(fn ($p) => str_ends_with($p, '.vue'))
        ->values()->all();
}

it('has a help panel on every admin page that has a page header', function () {
    $missing = collect(adminPages())
        ->filter(fn ($p) => str_contains(file_get_contents($p), '<PageHeader') && ! str_contains(file_get_contents($p), ':help="'))
        ->map(fn ($p) => str_replace(resource_path('js/pages/'), '', $p))
        // The Help page is the help.
        ->reject(fn ($p) => $p === 'admin/Help.vue')
        ->values()->all();

    expect($missing)->toBe([]);
});

it('links every "More in Help" button to a real Help entry', function () {
    $help = file_get_contents(resource_path('js/pages/admin/Help.vue'));
    preg_match_all("/q: '((?:[^'\\\\]|\\\\.)*)'/u", $help, $q);
    $questions = array_map(fn ($s) => stripslashes($s), $q[1]);

    $topics = collect(adminPages())
        ->flatMap(function ($p) {
            preg_match_all('/help-topic="([^"]+)"/', file_get_contents($p), $m);

            return $m[1];
        })->all();

    expect($topics)->not->toBeEmpty();

    foreach ($topics as $topic) {
        expect($questions)->toContain(html_entity_decode($topic));
    }
});

it('explains every kind of printed paper and every new tool in the Help page', function () {
    $help = file_get_contents(resource_path('js/pages/admin/Help.vue'));

    foreach (['puppy schedule', 'go-home packet', 'sales agreement', 'deposit receipt', 'stud agreement', 'whelping log', 'money', 'Bred with H.E.A.R.T.', 'Breeder of Merit', 'Email everyone waiting'] as $needle) {
        expect(mb_strtolower($help))->toContain(mb_strtolower($needle));
    }
});

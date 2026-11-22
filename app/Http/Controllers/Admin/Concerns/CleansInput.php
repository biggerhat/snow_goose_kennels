<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

/** Accept what people actually type: "$1,500", "1 500", "1500.00", "(555) 123 4567". */
trait CleansInput
{
    /** @param list<string> $keys */
    protected function cleanMoney(Request $request, array $keys): void
    {
        foreach ($keys as $key) {
            $value = $request->input($key);

            if (! is_string($value)) {
                continue;
            }

            $clean = preg_replace('/[\s$,]/', '', $value);
            $clean = preg_replace('/\.0{1,2}$/', '', (string) $clean);

            $request->merge([$key => $clean === '' ? null : $clean]);
        }
    }

    /** @param list<string> $keys */
    protected function cleanDigits(Request $request, array $keys): void
    {
        foreach ($keys as $key) {
            $value = $request->input($key);

            if (is_string($value)) {
                $clean = preg_replace('/[\s-]/', '', $value);
                $request->merge([$key => $clean === '' ? null : $clean]);
            }
        }
    }
}

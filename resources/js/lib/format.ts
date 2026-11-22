export function formatDate(
    value: string | null | undefined,
    withYear = true,
): string {
    if (!value) {
        return '—';
    }

    const [y, m, d] = value.slice(0, 10).split('-').map(Number);
    const date = new Date(y, m - 1, d);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        ...(withYear ? { year: 'numeric' } : {}),
    });
}

export function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function money(value: number | null | undefined): string {
    return value == null ? '—' : `$${value.toLocaleString('en-US')}`;
}

/** Whole days from today to the given Y-m-d date (negative when past). */
export function daysUntil(value: string | null | undefined): number | null {
    if (!value) {
        return null;
    }

    const [y, m, d] = value.slice(0, 10).split('-').map(Number);
    const target = new Date(y, m - 1, d).getTime();
    const today = new Date();
    const start = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    ).getTime();

    return Math.round((target - start) / 86_400_000);
}

export function countdown(value: string | null | undefined): string | null {
    const days = daysUntil(value);

    if (days === null) {
        return null;
    }

    if (days === 0) {
        return 'today';
    }

    const abs = Math.abs(days);
    const label =
        abs < 14
            ? `${abs} day${abs === 1 ? '' : 's'}`
            : `${Math.round(abs / 7)} weeks`;

    return days > 0 ? `in ${label}` : `${label} ago`;
}

export function labelFor(
    options: { value: string; label: string }[],
    value: string | null | undefined,
): string {
    return options.find((o) => o.value === value)?.label ?? '';
}

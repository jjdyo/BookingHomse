export function normalizeDateTimeToIso(value: string, timeZone: string = import.meta.env.VITE_TZ || 'UTC'): string {
    if (!value) return value;

    // value is expected to be in YYYY-MM-DDTHH:mm format from datetime-local input
    // If it already looks like an ISO string with TZ info, just return it
    if (value.includes('Z') || (value.includes('+') && value.lastIndexOf(':') > value.lastIndexOf('+'))) {
        return value;
    }

    try {
        // We want to treat the "wall clock" time in 'value' as being in 'timeZone'
        // Intl.DateTimeFormat can help us find the offset, but simpler is often using
        // the fact that we can construct a string that JS Date will parse as that TZ.

        // However, many browsers don't support TZ in the Date constructor string reliably
        // for all IANA names without a library like luxon or date-fns-tz.

        // Given we want to avoid extra heavy dependencies if possible, and we know
        // the calendar uses this same VITE_TZ, we can use a trick:
        // Format the date as if it were in the target TZ, find the difference from UTC.

        const date = new Date(value);
        if (isNaN(date.getTime())) return value;

        // If timeZone is UTC, we can just use toISOString but we must ensure
        // the local time selected in the picker is treated as UTC.
        if (timeZone.toUpperCase() === 'UTC') {
            return value.includes('Z') ? value : value + ':00.000Z';
        }

        // For non-UTC timezones, we need to know the offset of 'value' in 'timeZone'
        // at that specific time (to handle DST).
        const parts = new Intl.DateTimeFormat('en-US', {
            timeZone,
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            second: 'numeric',
            hour12: false,
        }).formatToParts(date);

        const map: Record<string, string> = {};
        parts.forEach(p => map[p.type] = p.value);

        const tzDate = new Date(`${map.year}-${map.month.padStart(2, '0')}-${map.day.padStart(2, '0')}T${map.hour.padStart(2, '0')}:${map.minute.padStart(2, '0')}:${map.second.padStart(2, '0')}`);

        const offset = tzDate.getTime() - date.getTime();
        const correctDate = new Date(date.getTime() - offset);

        return correctDate.toISOString();
    } catch (e) {
        console.error('Error normalizing date', e);
        return new Date(value).toISOString();
    }
}

export function formatIsoToInputDateTime(value: string | Date | null | undefined): string {
    if (!value) return '';
    const d = typeof value === 'string' ? new Date(value) : value;
    if (!d || Number.isNaN(d.getTime())) return '';

    // If we want to show it in the site's timezone (VITE_TZ)
    const timeZone = import.meta.env.VITE_TZ || 'UTC';

    try {
        const formatter = new Intl.DateTimeFormat('sv-SE', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });

        // sv-SE gives YYYY-MM-DD HH:mm
        return formatter.format(d).replace(' ', 'T');
    } catch (e) {
        // Fallback to local if TZ fails
        const pad = (n: number) => String(n).padStart(2, '0');
        const yyyy = d.getFullYear();
        const mm = pad(d.getMonth() + 1);
        const dd = pad(d.getDate());
        const hh = pad(d.getHours());
        const mi = pad(d.getMinutes());
        return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
    }
}

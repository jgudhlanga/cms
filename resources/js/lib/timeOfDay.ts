export type TimeOfDay = 'morning' | 'afternoon' | 'evening';

export function resolveUserTimeZone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function getHourInTimeZone(date: Date, timeZone: string): number {
    const hourPart = new Intl.DateTimeFormat('en-GB', {
        hour: 'numeric',
        hour12: false,
        timeZone,
    })
        .formatToParts(date)
        .find((part) => part.type === 'hour');

    return Number(hourPart?.value ?? date.getHours());
}

export function getTimeOfDay(hour: number): TimeOfDay {
    if (hour < 12) {
        return 'morning';
    }

    if (hour < 17) {
        return 'afternoon';
    }

    return 'evening';
}

export function getTimeOfDayForDate(date: Date = new Date(), timeZone?: string): TimeOfDay {
    const zone = timeZone ?? resolveUserTimeZone();

    return getTimeOfDay(getHourInTimeZone(date, zone));
}

export function timeOfDayGreetingKey(timeOfDay: TimeOfDay): string {
    return `students.dashboard_greeting_${timeOfDay}`;
}

import type { Updater } from '@tanstack/vue-table';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref } from 'vue';
import { RouteParams } from 'ziggy-js';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
    ref.value = typeof updaterOrValue === 'function' ? updaterOrValue(ref.value) : updaterOrValue;
}

export function getIdParams(id: string): RouteParams<string> {
    return id as unknown as RouteParams<string>;
}

/**
 * Flattens a modal edit payload into positive numeric ids. A partial list here
 * would unlink the ids left out, so anything unrecognised yields an empty list.
 */
export function toIdList(value: unknown): number[] {
    const candidates = Array.isArray(value) ? value.flat(Infinity) : [value];

    return candidates.map((entry) => Number(entry)).filter((entry) => Number.isInteger(entry) && entry > 0);
}

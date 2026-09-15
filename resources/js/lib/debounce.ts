export type Debounced<Fn extends (...args: any[]) => unknown> = ((...args: Parameters<Fn>) => void) & { cancel: () => void };

/**
 * Trailing-edge debounce: runs `fn` once, `wait` ms after the last call, with that call's arguments.
 * Replaces lodash's debounce, which pulled a 72 KB chunk into every table page.
 */
// `any[]` keeps untyped callback parameters as `any`, matching lodash's typing that callers relied on.
export function debounce<Fn extends (...args: any[]) => unknown>(fn: Fn, wait = 0): Debounced<Fn> {
    let timer: ReturnType<typeof setTimeout> | undefined;

    const debounced = (...args: Parameters<Fn>): void => {
        if (timer !== undefined) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            timer = undefined;
            fn(...args);
        }, wait);
    };

    debounced.cancel = (): void => {
        if (timer !== undefined) {
            clearTimeout(timer);
            timer = undefined;
        }
    };

    return debounced;
}

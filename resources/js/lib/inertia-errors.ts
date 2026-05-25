/**
 * Returns the first non-empty validation message from an Inertia form error bag.
 */
export function firstInertiaErrorMessage(errors: Record<string, string | string[]>, fallback: string): string {
    for (const value of Object.values(errors)) {
        const message = Array.isArray(value) ? value[0] : value;
        if (message) {
            return message;
        }
    }

    return fallback;
}

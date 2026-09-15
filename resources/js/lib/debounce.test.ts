import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { debounce } from '@/lib/debounce';

describe('debounce', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('runs once after the last call, with its arguments', () => {
        const fn = vi.fn();
        const search = debounce(fn, 300);

        search('a');
        vi.advanceTimersByTime(200);
        search('ab');
        vi.advanceTimersByTime(299);
        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(fn).toHaveBeenCalledTimes(1);
        expect(fn).toHaveBeenCalledWith('ab');
    });

    it('runs again for calls after the wait has passed', () => {
        const fn = vi.fn();
        const save = debounce(fn, 100);

        save(1);
        vi.advanceTimersByTime(100);
        save(2);
        vi.advanceTimersByTime(100);

        expect(fn.mock.calls).toEqual([[1], [2]]);
    });

    it('can cancel a pending call', () => {
        const fn = vi.fn();
        const search = debounce(fn, 100);

        search('x');
        search.cancel();
        vi.advanceTimersByTime(100);

        expect(fn).not.toHaveBeenCalled();
    });
});

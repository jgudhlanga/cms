import { afterEach, describe, expect, it, vi } from 'vitest';

import { cachedLookup, clearDropdownCache } from '@/lib/dropdownCache';

describe('cachedLookup', () => {
    afterEach(() => {
        clearDropdownCache();
    });

    it('reuses a loaded value until it expires', async () => {
        let clock = 1_000;
        const load = vi.fn().mockResolvedValue(['Male', 'Female']);

        await cachedLookup('genders', load, 100, () => clock);
        await cachedLookup('genders', load, 100, () => clock);
        expect(load).toHaveBeenCalledTimes(1);

        clock += 101;
        await cachedLookup('genders', load, 100, () => clock);
        expect(load).toHaveBeenCalledTimes(2);
    });

    it('collapses simultaneous requests for the same key', async () => {
        const load = vi.fn().mockResolvedValue(['Mr']);

        const [first, second] = await Promise.all([cachedLookup('titles', load), cachedLookup('titles', load)]);

        expect(load).toHaveBeenCalledTimes(1);
        expect(first).toBe(second);
    });

    it('does not cache failed loads', async () => {
        const load = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue(['Harare']);

        await expect(cachedLookup('provinces', load)).rejects.toThrow('offline');
        await expect(cachedLookup('provinces', load)).resolves.toEqual(['Harare']);
        expect(load).toHaveBeenCalledTimes(2);
    });

    it('clears entries by key prefix', async () => {
        const genders = vi.fn().mockResolvedValue([]);
        const titles = vi.fn().mockResolvedValue([]);

        await cachedLookup('7|genders', genders);
        await cachedLookup('8|titles', titles);
        clearDropdownCache('7|');
        await cachedLookup('7|genders', genders);
        await cachedLookup('8|titles', titles);

        expect(genders).toHaveBeenCalledTimes(2);
        expect(titles).toHaveBeenCalledTimes(1);
    });

    it('does not keep a result that finished after the cache was cleared', async () => {
        let resolveLoad: (value: string[]) => void = () => {};
        const slow = vi.fn(() => new Promise<string[]>((resolve) => (resolveLoad = resolve)));
        const fresh = vi.fn().mockResolvedValue(['new']);

        const pending = cachedLookup('levels', slow);
        clearDropdownCache();
        resolveLoad(['old']);
        await pending;

        await expect(cachedLookup('levels', fresh)).resolves.toEqual(['new']);
    });
});

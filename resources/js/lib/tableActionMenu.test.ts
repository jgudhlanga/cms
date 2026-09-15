import { describe, expect, it, vi } from 'vitest';
import { buildTableActionMenuGroups, filterTableActionOptions } from './tableActionMenu';
import type { ButtonDropdownOption } from '@/types/tables';

vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string) => key,
    trans_choice: (key: string) => key,
}));

const options = (keys: string[]): ButtonDropdownOption[] =>
    keys.map((key) => ({ key, action: vi.fn() }));

describe('filterTableActionOptions', () => {
    it('keeps only restore when the row is archived', () => {
        const filtered = filterTableActionOptions(true, options(['view', 'edit', 'restore', 'delete']));

        expect(filtered.map((item) => item.key)).toEqual(['restore']);
    });

    it('drops restore when the row is active', () => {
        const filtered = filterTableActionOptions(false, options(['view', 'edit', 'restore', 'delete']));

        expect(filtered.map((item) => item.key)).toEqual(['view', 'edit', 'delete']);
    });
});

describe('buildTableActionMenuGroups', () => {
    it('returns a single actions group with mapped labels and icons', () => {
        const groups = buildTableActionMenuGroups(false, options(['view', 'edit', 'archive', 'delete']));

        expect(groups).toHaveLength(1);
        expect(groups[0].key).toBe('actions');
        expect(groups[0].items.map((item) => item.key)).toEqual(['view', 'edit', 'archive', 'delete']);
        expect(groups[0].items.map((item) => item.label)).toEqual([
            'trans.view',
            'trans.edit',
            'trans.archive',
            'trans.force_delete',
        ]);
        expect(groups[0].items.find((item) => item.key === 'delete')?.danger).toBe(true);
    });

    it('returns no groups when every option is filtered out', () => {
        expect(buildTableActionMenuGroups(true, options(['view', 'edit']))).toEqual([]);
    });

    it('skips unknown option keys', () => {
        const groups = buildTableActionMenuGroups(false, options(['view', 'mystery']));

        expect(groups[0].items.map((item) => item.key)).toEqual(['view']);
    });

    it('preserves the original action callback', () => {
        const action = vi.fn();
        const groups = buildTableActionMenuGroups(false, [{ key: 'view', action }]);

        groups[0].items[0].action?.();

        expect(action).toHaveBeenCalledOnce();
    });
});

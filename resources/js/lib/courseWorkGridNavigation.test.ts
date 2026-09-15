import { describe, expect, it } from 'vitest';

import { gridCellSelector, nextGridCell, type GridMoveInput } from '@/lib/courseWorkGridNavigation';

const move = (overrides: Partial<GridMoveInput>): ReturnType<typeof nextGridCell> =>
    nextGridCell({
        key: 'ArrowDown',
        rowIndex: 1,
        columnIndex: 1,
        rowCount: 3,
        columnCount: 3,
        caretAtStart: false,
        caretAtEnd: false,
        ...overrides,
    });

describe('nextGridCell', () => {
    it('moves between students with the up and down arrows and stops at the edges', () => {
        expect(move({ key: 'ArrowUp' })).toEqual({ rowIndex: 0, columnIndex: 1 });
        expect(move({ key: 'ArrowDown' })).toEqual({ rowIndex: 2, columnIndex: 1 });
        expect(move({ key: 'ArrowUp', rowIndex: 0 })).toBeNull();
        expect(move({ key: 'ArrowDown', rowIndex: 2 })).toBeNull();
    });

    it('moves down with Enter and up with Shift+Enter', () => {
        expect(move({ key: 'Enter' })).toEqual({ rowIndex: 2, columnIndex: 1 });
        expect(move({ key: 'Enter', shiftKey: true })).toEqual({ rowIndex: 0, columnIndex: 1 });
    });

    it('only leaves a cell sideways when the caret is at that edge', () => {
        expect(move({ key: 'ArrowLeft' })).toBeNull();
        expect(move({ key: 'ArrowLeft', caretAtStart: true })).toEqual({ rowIndex: 1, columnIndex: 0 });
        expect(move({ key: 'ArrowRight', caretAtEnd: true })).toEqual({ rowIndex: 1, columnIndex: 2 });
        expect(move({ key: 'ArrowRight', caretAtEnd: true, columnIndex: 2 })).toBeNull();
    });

    it('ignores other keys', () => {
        expect(move({ key: 'Tab' })).toBeNull();
        expect(move({ key: '7' })).toBeNull();
    });

    it('builds a selector for the target cell', () => {
        expect(gridCellSelector({ rowIndex: 2, columnIndex: 0 })).toBe('[data-course-work-cell="2-0"]');
    });
});

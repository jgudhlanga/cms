export interface GridMoveInput {
    key: string;
    rowIndex: number;
    columnIndex: number;
    rowCount: number;
    columnCount: number;
    /** Caret sits at the very start of the input (so ArrowLeft leaves the cell). */
    caretAtStart: boolean;
    /** Caret sits at the very end of the input (so ArrowRight leaves the cell). */
    caretAtEnd: boolean;
    shiftKey?: boolean;
}

export interface GridCell {
    rowIndex: number;
    columnIndex: number;
}

/**
 * Spreadsheet-style movement between mark cells: ArrowUp/ArrowDown and Enter (Shift+Enter upward) move
 * between students; ArrowLeft/ArrowRight move between assessments only when the caret is already at the
 * edge, so editing inside a value still works. Returns null when focus should stay put.
 */
export const nextGridCell = (input: GridMoveInput): GridCell | null => {
    const { key, rowIndex, columnIndex, rowCount, columnCount } = input;
    const up = rowIndex > 0 ? { rowIndex: rowIndex - 1, columnIndex } : null;
    const down = rowIndex < rowCount - 1 ? { rowIndex: rowIndex + 1, columnIndex } : null;

    switch (key) {
        case 'ArrowUp':
            return up;
        case 'ArrowDown':
            return down;
        case 'Enter':
            return input.shiftKey ? up : down;
        case 'ArrowLeft':
            return input.caretAtStart && columnIndex > 0 ? { rowIndex, columnIndex: columnIndex - 1 } : null;
        case 'ArrowRight':
            return input.caretAtEnd && columnIndex < columnCount - 1 ? { rowIndex, columnIndex: columnIndex + 1 } : null;
        default:
            return null;
    }
};

export const gridCellSelector = (cell: GridCell): string => `[data-course-work-cell="${cell.rowIndex}-${cell.columnIndex}"]`;

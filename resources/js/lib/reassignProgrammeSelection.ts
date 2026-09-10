export function pruneSelectionToVisible(selectedIds: number[], visibleIds: number[]): number[] {
    if (visibleIds.length === 0) {
        return [];
    }

    const visible = new Set(visibleIds);

    return selectedIds.filter((id) => visible.has(id));
}

export function applicationIdsForSubmit(
    selectedIds: number[],
    visibleIds: number[],
    filterModeIds: number[],
): number[] {
    if (filterModeIds.length === 0) {
        return selectedIds;
    }

    return pruneSelectionToVisible(selectedIds, visibleIds);
}

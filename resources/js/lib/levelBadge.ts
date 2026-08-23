export function formatLevelBadge(levelName: string): string {
    const words = levelName.trim().split(/\s+/);
    if (words.length === 1) {
        return levelName.slice(0, 3).toUpperCase();
    }

    return words
        .map((word) => word[0] ?? '')
        .join('')
        .slice(0, 3)
        .toUpperCase();
}

export function shortClassNumberLabel(className: string): string {
    const match = className.trim().match(/-(\d+)$/);

    return match?.[1] ?? className;
}

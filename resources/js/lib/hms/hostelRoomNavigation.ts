import { currentPageReturnPath, resolveSafeReturnUrl } from '@/lib/studentShowNavigation';

export function buildHostelRoomShowUrl(
    roomId: string | number,
    options: { return?: string } = {},
): string {
    const base = route('hostel-rooms.show', String(roomId));

    if (!options.return) {
        return base;
    }

    const separator = base.includes('?') ? '&' : '?';

    return `${base}${separator}return=${encodeURIComponent(options.return)}`;
}

export function resolveHostelRoomBackUrl(
    returnParam: string | null | undefined,
    hostelId: string | number,
    origin = typeof window !== 'undefined' ? window.location.origin : 'https://localhost',
): string {
    return resolveSafeReturnUrl(returnParam, origin)
        ?? route('hostels.show', String(hostelId));
}

export function currentHostelPageReturnPath(pageUrl: string, origin?: string): string {
    return currentPageReturnPath(pageUrl, origin);
}

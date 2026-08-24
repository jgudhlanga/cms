import { colorFromDepartment } from '@/lib/departmentDistributionPresentation';

const HEX_COLOR_PATTERN = /^#[0-9A-Fa-f]{6}$/;

export function isValidDepartmentColor(color?: string | null): color is string {
    return typeof color === 'string' && HEX_COLOR_PATTERN.test(color);
}

export function resolveDepartmentColor(colorCode?: string | null, fallbackName?: string | null, alpha = 0.7): string {
    if (isValidDepartmentColor(colorCode)) {
        if (alpha >= 1) {
            return colorCode;
        }

        const hex = colorCode.slice(1);
        const r = Number.parseInt(hex.slice(0, 2), 16);
        const g = Number.parseInt(hex.slice(2, 4), 16);
        const b = Number.parseInt(hex.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    if (fallbackName) {
        return colorFromDepartment(fallbackName, alpha);
    }

    return `rgba(100, 116, 139, ${alpha})`;
}

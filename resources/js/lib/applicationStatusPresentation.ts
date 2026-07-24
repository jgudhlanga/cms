import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';

const APPLICATION_STATUS_SORT_PRIORITY: Record<string, number> = {
    Enrolled: 0,
    Accepted: 1,
    Review: 2,
    Requirements: 3,
    'Registration Fee': 4,
    Waitlisted: 5,
    Rejected: 6,
    Unsuccessful: 7,
};

export function applicationStatusSortPriority(step: string | null | undefined): number {
    if (!step) {
        return 99;
    }

    return APPLICATION_STATUS_SORT_PRIORITY[step] ?? 98;
}

export function applicationStatusVariant(step: string): ColorVariant {
    switch (step) {
        case 'Review':
            return ColorVariant.info;
        case 'Requirements':
        case 'Waitlisted':
            return ColorVariant.warning;
        case 'Accepted':
        case 'Enrolled':
            return ColorVariant.success;
        case 'Rejected':
        case 'Unsuccessful':
            return ColorVariant.danger;
        default:
            return ColorVariant.shade;
    }
}

export function applicationStatusAlertType(step: string): TypeVariant {
    switch (step) {
        case 'Review':
            return TypeVariant.info;
        case 'Requirements':
        case 'Waitlisted':
            return TypeVariant.warning;
        case 'Accepted':
        case 'Enrolled':
            return TypeVariant.success;
        case 'Rejected':
        case 'Unsuccessful':
            return TypeVariant.danger;
        default:
            return TypeVariant.info;
    }
}

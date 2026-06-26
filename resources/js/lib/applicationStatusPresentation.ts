import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';

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

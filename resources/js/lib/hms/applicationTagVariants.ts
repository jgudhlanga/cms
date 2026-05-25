import { ColorVariant } from '@/enums/colors';
import type { HostelApplicationStatus, HostelApplicationType } from '@/types/hms';

export function hostelApplicationTypeTagVariant(type: HostelApplicationType): ColorVariant {
    return type === 'guest'
        ? ColorVariant.fuchsia_outline
        : ColorVariant.success_outline;
}

export function hostelApplicationStatusTagVariant(status: HostelApplicationStatus): ColorVariant {
    switch (status) {
        case 'pending':
            return ColorVariant.warning_outline;
        case 'awaiting-payment':
            return ColorVariant.primary_outline;
        case 'approved':
            return ColorVariant.success_outline;
        case 'declined':
            return ColorVariant.danger_outline;
        default:
            return ColorVariant.primary_outline;
    }
}

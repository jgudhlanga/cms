import { ColorVariant } from '@/enums/colors';
import type { IdCardRequestStatus } from '@/types/id-cards';

export function idCardRequestStatusTagVariant(status: IdCardRequestStatus | string | null | undefined): ColorVariant {
    switch (status) {
        case 'awaiting_payment':
            return ColorVariant.primary_outline;
        case 'pending':
            return ColorVariant.warning_outline;
        case 'approved':
            return ColorVariant.success_outline;
        case 'rejected':
            return ColorVariant.danger_outline;
        case 'printed':
            return ColorVariant.info_outline;
        case 'issued':
            return ColorVariant.success;
        default:
            return ColorVariant.shade_outline;
    }
}

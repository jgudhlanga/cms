import { useUtils } from '@/composables/core/useUtils';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';

export function getUserAbilities(): string[] {
    const { props } = usePage<PageProps>();
    const { can } = props?.auth;
    const abilities: Record<string, boolean> = can;
    if (!abilities) return [];
    return Object.keys(abilities).filter((ability) => can[ability]);
}

/**
 * Checks if user has at least one of the required abilities.
 */
export function hasAbility(required: string | string[]): boolean {
    const userAbilities = getUserAbilities();
    const requiredList = Array.isArray(required) ? required : [required];

    return requiredList.some((ability) => userAbilities.includes(ability));
}

export function hasStudentProfile(): boolean {
    const { isItTrue } = useUtils();
    const { auth } = usePage<PageProps>().props;
    return isItTrue(auth?.user?.attributes?.hasStudentProfile);
}

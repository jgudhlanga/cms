import { usePage } from '@inertiajs/vue3';
import { PageProps } from '@/types';

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
export function hasAbility(
    required: string | string[]
): boolean {
    const userAbilities = getUserAbilities();
    const requiredList = Array.isArray(required) ? required : [required];

    return requiredList.some((ability) => userAbilities.includes(ability));
}

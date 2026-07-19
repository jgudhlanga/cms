import { useUtils } from '@/composables/core/useUtils';
import { ModuleState, PageProps } from '@/types';
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

export function hasProgram(): boolean {
    const { isItTrue } = useUtils();
    const { auth } = usePage<PageProps>().props;
    return isItTrue(auth?.user?.attributes?.hasProgram);
}

const DASHBOARD_TAB_PERMISSIONS = [
    'view:dashboards',
    'viewAny:dashboards',
    'view:lecturer-dashboard',
    'view-academic:dashboards',
    'view-enrolment:dashboards',
    'view-attendance:dashboards',
    'view-staff:dashboards',
    'view-finance:dashboards',
    'view-hostel:dashboards',
] as const;

export function getModuleState(): ModuleState {
    return usePage<PageProps>().props.moduleState ?? {};
}

export function isModuleEnabled(slug: string, moduleState?: ModuleState): boolean {
    const state = moduleState ?? getModuleState();

    return state[slug]?.enabled ?? true;
}

export function isDashboardModuleEnabled(moduleState?: ModuleState): boolean {
    return isModuleEnabled('dashboards', moduleState);
}

export function hasDashboardAccess(moduleState?: ModuleState): boolean {
    return isDashboardModuleEnabled(moduleState) && hasAbility([...DASHBOARD_TAB_PERMISSIONS]);
}

export function canShowMenuItem(permission: string | string[], moduleSlug?: string, moduleState?: ModuleState): boolean {
    const state = moduleState ?? getModuleState();

    if (moduleSlug && !isModuleEnabled(moduleSlug, state)) {
        return false;
    }

    return hasAbility(permission);
}

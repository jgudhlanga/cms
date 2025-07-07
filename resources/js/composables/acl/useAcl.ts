import { Link } from '@/types/ui';

export function useAcl() {
    const tabs: Array<Link> = [
        {
            transChoiceKey: 'module',
            url: route('modules.index'),
        },
        {
            transChoiceKey: 'permission',
            url: route('permissions.index'),
        },
        {
            transChoiceKey: 'role_group',
            url: route('role-groups.index'),
        },
        {
            transChoiceKey: 'role',
            url: route('roles.index'),
        },
    ];
    return { tabs };
}

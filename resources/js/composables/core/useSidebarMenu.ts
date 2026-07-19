import AppLogo from '@/components/core/image/AppLogo.vue';
import {
    isStudentProfileTabVisible,
    portalSidebarProfileTabs,
    type StudentProfileTabValue,
} from '@/composables/students/useStudentProfileTabs';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { canShowMenuItem, hasAbility, hasDashboardAccess, hasStudentProfile } from '@/lib/permissions';
import { PageProps } from '@/types';
import { TenantInterface } from '@/types/tenants';
import { MenuGroupInterface, MenuGroupKey, MenuItemInterface } from '@/types/ui';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { computed, markRaw } from 'vue';

const menuGroupOrder: MenuGroupKey[] = [
    'overview',
    'lecturer',
    'students',
    'operations',
    'institution',
    'system',
    'department',
    'portal',
];

export function getMenuItemKey(item: MenuItemInterface): string {
    return item.url ?? item.transKey ?? item.transChoiceKey ?? item.title ?? '';
}

export function useSidebarMenu() {
    const page = usePage<PageProps>();

    const tenants: Array<TenantInterface> = [
        {
            id: '1',
            type: 'tenant',
            attributes: {
                name: 'Harare Poly',
                isDefault: true,
                logo: markRaw(AppLogo),
                bio: 'Software',
            },
        },
    ];

    const menuOptions = computed<MenuItemInterface[]>(() => {
        const moduleState = page.props.moduleState ?? {};

        return [
        {
            groupKey: 'overview',
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('dashboard'),
            show: hasDashboardAccess(moduleState),
        },
        {
            groupKey: 'lecturer',
            transChoiceKey: 'trans.class',
            icon: icons[IconName.users],
            url: route('teaching.classes.index'),
            show: canShowMenuItem('view:lecturer-classes', 'institution', moduleState),
        },
        {
            groupKey: 'lecturer',
            transChoiceKey: 'trans.module',
            icon: icons[IconName.book_check],
            url: route('teaching.modules.index'),
            show: canShowMenuItem('view:lecturer-modules', 'institution', moduleState),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('enrolments.index'),
            show: canShowMenuItem('view:student-applications', 'enrolments', moduleState),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('students.index'),
            show: canShowMenuItem('view:students', 'students', moduleState),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('examinations.index'),
            show: canShowMenuItem(
                ['viewAny:examinations', 'view:examinations'],
                'examinations',
                moduleState,
            ),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.communication',
            url: '#',
            icon: icons[IconName.person_chat],
            show: false /*hasAbility('view:communication')*/,
        },
        {
            groupKey: 'operations',
            transChoiceKey: 'trans.report',
            url: '#',
            icon: icons[IconName.report],
            show: canShowMenuItem('view:report', 'reports', moduleState),
        },
        {
            groupKey: 'operations',
            transChoiceKey: 'finance.financial',
            url: route('finance.index'),
            icon: icons[IconName.dollar],
            show: canShowMenuItem('view:finances', 'finance', moduleState),
        },
        {
            groupKey: 'institution',
            transChoiceKey: 'trans.institution',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: canShowMenuItem('view:institution-settings', 'institution', moduleState),
        },
        {
            groupKey: 'institution',
            transChoiceKey: 'hms.title',
            icon: icons[IconName.bed],
            url: route('hostels.index'),
            show: canShowMenuItem('view:hostels', 'hms', moduleState),
        },
        {
            groupKey: 'system',
            transKey: 'trans.settings',
            url: route('settings.index'),
            icon: icons[IconName.cogs],
            show: canShowMenuItem('view:settings', 'settings', moduleState),
        },
        {
            groupKey: 'system',
            transChoiceKey: 'trans.user',
            url: route('users.index'),
            icon: icons[IconName.users],
            show: canShowMenuItem('view:users', 'users', moduleState),
        },
        {
            groupKey: 'system',
            transKey: 'trans.maintenance',
            url: route('maintenance.index'),
            icon: icons[IconName.maintenance],
            show: canShowMenuItem('root:manage', 'root', moduleState),
        },
        {
            groupKey: 'department',
            title: 'My Departments',
            transChoiceKeyIndex: 1,
            url: route('institution-departments.index', { is_academic: 1 }),
            icon: icons[IconName.school],
            show: canShowMenuItem('viewOnlyOwnDepartment:departments', 'institution', moduleState),
        },
        {
            groupKey: 'portal',
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('portal.dashboard'),
            show: hasAbility('viewOwnDashboard:students') && hasStudentProfile(),
        },
        ...portalSidebarProfileTabs()
            .filter((tab) => tab.value !== 'authentication')
            .map((tab) => ({
                groupKey: 'portal' as const,
                title: tab.transLabel(),
                icon: icons[tab.icon],
                url: route(tab.routeName!),
                show:
                    hasStudentProfile()
                    && isStudentProfileTabVisible(tab.value as StudentProfileTabValue, 'portal'),
            })),
        {
            groupKey: 'portal',
            title: 'O Levels',
            icon: icons[IconName.award],
            url: route('portal.list-o-levels'),
            show: hasStudentProfile() && hasAbility('manageOwnStudentAcademicDetails:students'),
        },
        ...portalSidebarProfileTabs()
            .filter((tab) => tab.value === 'authentication')
            .map((tab) => ({
                groupKey: 'portal' as const,
                title: tab.transLabel(),
                icon: icons[tab.icon],
                url: route(tab.routeName!),
                show:
                    hasStudentProfile()
                    && isStudentProfileTabVisible(tab.value as StudentProfileTabValue, 'portal'),
            })),
    ];
    });

    const menuGroups = computed<MenuGroupInterface[]>(() => {
        const visibleItems = menuOptions.value.filter((item) => item.show);

        const groups = menuGroupOrder
            .map((key) => ({
                key,
                items: visibleItems.filter((item) => item.groupKey === key),
            }))
            .filter((group) => group.items.length > 0);

        const hasAdminGroups = groups.some((group) => group.key !== 'portal');

        return groups.map((group) => ({
            ...group,
            showSeparatorBefore: group.key === 'portal' && hasAdminGroups,
        }));
    });

    const getTranslation = (item: MenuItemInterface) => {
        if (item?.transChoiceKey) {
            return trans_choice(item.transChoiceKey, item.transChoiceKeyIndex ?? 2);
        }
        if (item?.transKey) {
            return trans(item.transKey);
        }
        return item?.title ?? '';
    };

    const getGroupLabel = (groupKey: MenuGroupKey) => trans(`trans.nav_groups.${groupKey}`);

    return { menuOptions, menuGroups, tenants, getTranslation, getGroupLabel, getMenuItemKey };
}

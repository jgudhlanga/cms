import AppLogo from '@/components/core/image/AppLogo.vue';
import {
    isStudentProfileTabVisible,
    portalSidebarProfileTabs,
    type StudentProfileTabValue,
} from '@/composables/students/useStudentProfileTabs';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { hasAbility, hasStudentProfile } from '@/lib/permissions';
import { TenantInterface } from '@/types/tenants';
import { MenuGroupInterface, MenuGroupKey, MenuItemInterface } from '@/types/ui';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, markRaw } from 'vue';

const menuGroupOrder: MenuGroupKey[] = [
    'overview',
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

    const menuOptions = computed<MenuItemInterface[]>(() => [
        {
            groupKey: 'overview',
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('dashboard'),
            show: hasAbility('view:dashboards'),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('enrolments.index'),
            show: hasAbility('view:student-programs'),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('students.index'),
            show: hasAbility('view:students'),
        },
        {
            groupKey: 'students',
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('dashboard'),
            show: false /*hasAbility('view:examinations'),*/,
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
            show: hasAbility('view:report'),
        },
        {
            groupKey: 'operations',
            transChoiceKey: 'finance.financial',
            url: route('finance.index'),
            icon: icons[IconName.dollar],
            show: hasAbility('view:finances'),
        },
        {
            groupKey: 'institution',
            transChoiceKey: 'trans.institution',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: hasAbility('view:institution-settings'),
        },
        {
            groupKey: 'institution',
            transChoiceKey: 'hms.title',
            icon: icons[IconName.bed],
            url: route('hostels.index'),
            show: hasAbility('view:hostels'),
        },
        {
            groupKey: 'system',
            transKey: 'trans.settings',
            url: route('settings.index'),
            icon: icons[IconName.cogs],
            show: hasAbility('view:settings'),
        },
        {
            groupKey: 'system',
            transChoiceKey: 'trans.user',
            url: route('users.index'),
            icon: icons[IconName.users],
            show: hasAbility('view:users'),
        },
        {
            groupKey: 'system',
            transKey: 'trans.maintenance',
            url: route('maintenance.index'),
            icon: icons[IconName.maintenance],
            show: hasAbility('root:manage'),
        },
        {
            groupKey: 'department',
            title: 'My Departments',
            transChoiceKeyIndex: 1,
            url: route('institution-departments.index', { is_academic: 1 }),
            icon: icons[IconName.school],
            show: hasAbility('viewOnlyOwnDepartment:departments'),
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
    ]);

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

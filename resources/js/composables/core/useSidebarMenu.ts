import AppLogo from '@/components/core/image/AppLogo.vue';
import {
    isStudentProfileTabVisible,
    portalSidebarProfileTabs,
    type StudentProfileTabValue,
} from '@/composables/students/useStudentProfileTabs';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { useAcl } from '@/composables/acl/useAcl';
import { useSettings } from '@/composables/settings/useSettings';
import { useUtils } from '@/composables/core/useUtils';
import {
    canShowMenuItem,
    canViewInstitutionHubAcademicDepartments,
    canViewNonAcademicDepartmentsMenu,
    hasAbility,
    hasDashboardAccess,
    hasStudentProfile,
    isModuleEnabled,
} from '@/lib/permissions';
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
    'portal',
];

export function getMenuItemKey(item: MenuItemInterface): string {
    return [item.transKey, item.transChoiceKey, item.url, item.title].filter(Boolean).join('|') || 'menu-item';
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
        (() => {
            const canViewFinance = canShowMenuItem('view:finances', 'finance', moduleState);
            const canViewFinanceChildren = hasAbility(['view:finances', 'view:finance-settings']);
            const financeChildren: MenuItemInterface[] = [
                {
                    transChoiceKey: 'finance.reconciliation',
                    url: route('finance.reconciliation'),
                    show: canViewFinanceChildren,
                },
                {
                    transChoiceKey: 'finance.exchange_rate',
                    url: route('finance.exchange-rates.index'),
                    show: canViewFinanceChildren,
                },
            ].filter((child) => child.show);

            return {
                groupKey: 'operations' as const,
                transChoiceKey: 'finance.financial',
                url: route('finance.index'),
                icon: icons[IconName.dollar],
                items: financeChildren,
                show: canViewFinance,
            };
        })(),
        (() => {
            const institutionModuleOn = isModuleEnabled('institution', moduleState);
            const institutionChildren: MenuItemInterface[] = [
                {
                    transChoiceKey: 'trans.non_academic_department',
                    transChoiceKeyIndex: 2,
                    url: route('institution-departments.index', { is_academic: 0 }),
                    show: institutionModuleOn && canViewNonAcademicDepartmentsMenu(),
                },
                {
                    transChoiceKey: 'trans.academic_department',
                    transChoiceKeyIndex: 2,
                    url: route('institution-departments.index', { is_academic: 1 }),
                    show: institutionModuleOn && canViewInstitutionHubAcademicDepartments(),
                },
                {
                    transKey: 'trans.institution_config',
                    url: route('institution.setup'),
                    show: canShowMenuItem('view:institution-settings', 'institution', moduleState),
                },
                {
                    transKey: 'trans.ui_payments_debug',
                    url: route('integrations.payments.check-status-create'),
                    show: hasAbility('root:manage'),
                },
            ].filter((child) => child.show);

            return {
                groupKey: 'institution' as const,
                transChoiceKey: 'trans.institution',
                transChoiceKeyIndex: 1,
                url: route('institution.index'),
                icon: icons[IconName.school],
                items: institutionChildren,
                show: institutionChildren.length > 0,
            };
        })(),
        (() => {
            const hmsChildren: MenuItemInterface[] = [
                {
                    transChoiceKey: 'hms.hostel',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'hostels' }),
                    show: true,
                },
                {
                    transChoiceKey: 'hms.room',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'rooms' }),
                    show: true,
                },
                {
                    transChoiceKey: 'trans.student',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'students' }),
                    show: true,
                },
                {
                    transChoiceKey: 'hms.application',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'applications' }),
                    show: hasAbility('viewAny:hostel-applications'),
                },
                {
                    transChoiceKey: 'hms.amenity',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'amenities' }),
                    show: hasAbility(['viewAny:hostel-amenities', 'view:hostel-amenities']),
                },
                {
                    transChoiceKey: 'hms.settings',
                    transChoiceKeyIndex: 2,
                    url: route('hostels.index', { tab: 'settings' }),
                    show: hasAbility(['view:hms-settings', 'update:hms-settings', 'crud-settings:hms-settings']),
                },
            ].filter((child) => child.show);

            return {
                groupKey: 'institution' as const,
                transChoiceKey: 'hms.title',
                icon: icons[IconName.bed],
                url: route('hostels.index'),
                items: hmsChildren,
                show: canShowMenuItem('view:hostels', 'hms', moduleState),
            };
        })(),
        (() => {
            const canViewSettings = canShowMenuItem('view:settings', 'settings', moduleState);
            const aclChildren: MenuItemInterface[] = useAcl().tabs.map((tab) => ({
                transChoiceKey: tab.transChoiceKey,
                url: tab.url,
                show: canViewSettings,
            })).filter((child) => child.show);

            return {
                groupKey: 'system' as const,
                transKey: 'trans.access_control_list',
                url: route('settings.index'),
                icon: icons[IconName.shield],
                items: aclChildren,
                show: canViewSettings,
            };
        })(),
        (() => {
            const canViewSettings = canShowMenuItem('view:settings', 'settings', moduleState);
            const settingsChildren: MenuItemInterface[] = useSettings().tabs.map((tab) => ({
                transChoiceKey: tab.transChoiceKey,
                url: tab.url,
                show: canViewSettings,
            })).filter((child) => child.show);

            return {
                groupKey: 'system' as const,
                transKey: 'trans.settings',
                url: route('settings.index'),
                icon: icons[IconName.cogs],
                items: settingsChildren,
                show: canViewSettings,
            };
        })(),
        {
            groupKey: 'system',
            transChoiceKey: 'trans.user',
            url: route('users.index'),
            icon: icons[IconName.users],
            show: canShowMenuItem('view:users', 'users', moduleState),
        },
        (() => {
            const canMaintain = canShowMenuItem('root:manage', 'root', moduleState);
            const maintenanceChildren: MenuItemInterface[] = [
                {
                    transChoiceKey: 'trans.user',
                    transChoiceKeyIndex: 2,
                    url: route('maintenance.index', { tab: 'users' }),
                    show: canMaintain,
                },
                {
                    transKey: 'trans.staff',
                    url: route('maintenance.index', { tab: 'staff' }),
                    show: canMaintain,
                },
                {
                    transChoiceKey: 'trans.student',
                    transChoiceKeyIndex: 2,
                    url: route('maintenance.index', { tab: 'students' }),
                    show: canMaintain,
                },
                {
                    transKey: 'trans.maintenance_archives',
                    url: route('maintenance.index', { tab: 'archives' }),
                    show: canMaintain,
                },
            ].filter((child) => child.show);

            return {
                groupKey: 'system' as const,
                transKey: 'trans.maintenance',
                url: route('maintenance.index'),
                icon: icons[IconName.maintenance],
                items: maintenanceChildren,
                show: canMaintain,
            };
        })(),
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

    const { getTransFile } = useUtils();

    const getTranslation = (item: MenuItemInterface) => {
        if (item?.transChoiceKey) {
            return trans_choice(getTransFile(item), item.transChoiceKeyIndex ?? 2);
        }
        if (item?.transKey) {
            return trans(getTransFile(item));
        }
        return item?.title ?? '';
    };

    const getGroupLabel = (groupKey: MenuGroupKey) => trans(`trans.nav_groups.${groupKey}`);

    return { menuOptions, menuGroups, tenants, getTranslation, getGroupLabel, getMenuItemKey };
}

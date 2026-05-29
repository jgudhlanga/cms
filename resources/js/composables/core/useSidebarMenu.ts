import AppLogo from '@/components/core/image/AppLogo.vue';
import { useStudentProfile } from '@/composables/students/useStudentProfile';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { hasAbility, hasStudentProfile } from '@/lib/permissions';
import { TenantInterface } from '@/types/tenants';
import { MenuItemInterface } from '@/types/ui';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, markRaw } from 'vue';

export function useSidebarMenu() {
    const { portalSidebarProfileTabs } = useStudentProfile();
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
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('dashboard'),
            show: hasAbility('view:dashboards'),
        },
        {
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('enrolments.index'),
            show: hasAbility('view:student-programs'),
        },
        {
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('students.index'),
            show: hasAbility('view:students'),
        },
        {
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('dashboard'),
            show: false /*hasAbility('view:examinations'),*/,
        },
        {
            transChoiceKey: 'trans.communication',
            url: '#',
            icon: icons[IconName.person_chat],
            show: false /*hasAbility('view:communication')*/,
        },
        {
            transChoiceKey: 'trans.report',
            url: '#',
            icon: icons[IconName.report],
            show: hasAbility('view:report'),
        },
        {
            transChoiceKey: 'finance.financial',
            url: route('finance.index'),
            icon: icons[IconName.dollar],
            show: hasAbility('view:finances'),
        }, 
        {
            transChoiceKey: 'trans.institution',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: hasAbility('view:institution-settings'),
        },
        {
            transChoiceKey: 'hms.title',
            icon: icons[IconName.bed],
            url: route('hostels.index'),
            show: hasAbility('view:hostels'),
        },
        {
            transKey: 'trans.settings',
            url: route('settings.index'),
            icon: icons[IconName.cogs],
            show: hasAbility('view:settings'),
        },
        {
            transChoiceKey: 'trans.user',
            url: route('users.index'),
            icon: icons[IconName.users],
            show: hasAbility('view:users'),
        },
        /** ============ DEPARTMENT STAFF ======================*/
        {
            title: 'My Departments',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: hasAbility('viewOnlyOwnDepartment:departments'),
        },
        /** ================ PORTAL START ======================*/
        {
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('portal.dashboard'),
            show: hasAbility('viewOwnDashboard:students') && hasStudentProfile(),
        },
        ...portalSidebarProfileTabs()
            .filter((tab) => tab.value !== 'authentication')
            .map((tab) => ({
                title: tab.transLabel(),
                icon: icons[tab.icon],
                url: route(tab.routeName!),
                show: tab.portalShow?.() ?? false,
            })),
        {
            transKey: 'trans.academic_record',
            icon: icons[IconName.book_check],
            url: route('portal.academic-record'),
            show: hasAbility('manageOwnStudentAcademicDetails:students') && hasStudentProfile(),
        },
        {
            title: 'O Levels',
            icon: icons[IconName.award],
            url: route('portal.list-o-levels'),
            show: hasAbility('manageOwnStudentAcademicDetails:students'),
        },
        ...portalSidebarProfileTabs()
            .filter((tab) => tab.value === 'authentication')
            .map((tab) => ({
                title: tab.transLabel(),
                icon: icons[tab.icon],
                url: route(tab.routeName!),
                show: tab.portalShow?.() ?? false,
            })),
        /** ================ PORTAL END ======================*/
    ]);

    const getTranslation = (item: any) => {
        if (item?.transChoiceKey) {
            return trans_choice(item?.transChoiceKey, item?.transChoiceKeyIndex ?? 2);
        }
        if (item?.transKey) {
            return trans(item?.transKey);
        }
        return item?.title;
    };
    return { menuOptions, tenants, getTranslation };
}

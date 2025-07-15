import AppLogo from '@/components/core/image/AppLogo.vue';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { TenantInterface } from '@/types/tenants';
import { MenuItemInterface } from '@/types/ui';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { markRaw } from 'vue';

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

    const menuOptions: Array<MenuItemInterface> = [
        {
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('dashboard'),
            show: hasAbility('view:dashboards'),
        },
        {
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('dashboard'),
            show: hasAbility('view:enrolments'),
        },
        {
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('dashboard'),
            show: hasAbility('view:students'),
        },
        {
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('dashboard'),
            show: false, /*hasAbility('view:examinations'),*/
        },
        {
            transChoiceKey: 'trans.accommodation',
            icon: icons[IconName.bed],
            url: route('dashboard'),
            show: false /*hasAbility('view:accommodations')*/,
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
            transChoiceKey: 'trans.institution',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: hasAbility('view:institution-settings'),
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
        /** ================ PORTAL START ======================*/
        {
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('portal.dashboard'),
            show: hasAbility('viewOwnDashboard:students'),
        },
        {
            transKey: 'trans.personal_details',
            icon: icons[IconName.user],
            url: route('portal.personal-details'),
            show: hasAbility('manageOwnStudentPersonalDetails:students'),
        },
        {
            transChoiceKey: 'trans.program',
            icon: icons[IconName.graduation_cape],
            url: route('portal.programs'),
            show: hasAbility('manageOwnStudentProgramDetails:students'),
        },
        {
            transKey: 'trans.financial_record',
            icon: icons[IconName.dollar],
            url: route('portal.financial-record'),
            show: hasAbility('manageOwnStudentFinancialDetails:students'),
        },
        {
            transKey: 'trans.academic_record',
            icon: icons[IconName.award],
            url: route('portal.academic-record'),
            show: hasAbility('manageOwnStudentAcademicDetails:students'),
        },
        /** ================ PORTAL END ======================*/
    ];

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

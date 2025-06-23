import AppLogo from '@/components/core/image/AppLogo.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { PageProps } from '@/types';
import { TenantInterface } from '@/types/tenants';
import { MenuItemInterface } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { markRaw } from 'vue';

export function useSidebarMenu() {
    const { props } = usePage<PageProps>();
    const { can } = props?.auth;
    const { isItTrue } = useUtils();
    const tenants: Array<TenantInterface> = [
        {
            id: '1',
            type: 'tenant',
            attributes: {
                name: 'Harare Poly',
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
            show: isItTrue(can['view:dashboards']),
        },
        {
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('portal.dashboard'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transKey: 'trans.personal_details',
            icon: icons[IconName.user],
            url: route('portal.personal-details'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transChoiceKey: 'trans.program',
            icon: icons[IconName.graduation_cape],
            url: route('portal.programs'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transChoiceKey: 'trans.sponsor',
            icon: icons[IconName.wallet_cards],
            url: route('portal.sponsors'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transChoiceKey: 'trans.contact',
            icon: icons[IconName.contact],
            url: route('portal.contacts'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transKey: 'trans.financial_record',
            icon: icons[IconName.dollar],
            url: route('portal.financial-record'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transKey: 'trans.academic_record',
            icon: icons[IconName.award],
            url: route('portal.academic-record'),
            show: isItTrue(can['manageOwnData:students']),
        },
        {
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('dashboard'),
            show: isItTrue(can['view:enrolments']),
        },
        {
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('dashboard'),
            show: isItTrue(can['view:students']),
        },
        {
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('dashboard'),
            show: isItTrue(can['view:examinations']),
        },
        {
            transChoiceKey: 'trans.accommodation',
            icon: icons[IconName.bed],
            url: route('dashboard'),
            show: isItTrue(can['view:accommodations']),
        },
        {
            transChoiceKey: 'trans.communication',
            url: '#',
            icon: icons[IconName.person_chat],
            show: isItTrue(can['view:communication']),
        },
        {
            transChoiceKey: 'trans.report',
            url: '#',
            icon: icons[IconName.report],
            show: isItTrue(can['view:report']),
        },
        {
            transChoiceKey: 'trans.institution',
            transChoiceKeyIndex: 1,
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: isItTrue(can['view:institution-settings']),
        },
        {
            transKey: 'trans.settings',
            url: route('settings.index'),
            icon: icons[IconName.cogs],
            show: isItTrue(can['view:settings']),
        },
        {
            transChoiceKey: 'trans.user',
            url: route('users.index'),
            icon: icons[IconName.users],
            show: isItTrue(can['view:users']),
        },
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

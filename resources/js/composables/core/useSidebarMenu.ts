import { PageProps } from '@/types';
import { TenantInterface } from '@/types/tenants';
import AppLogo from '@/components/core/image/AppLogo.vue';
import { MenuItemInterface } from '@/types/ui';
import { usePage } from '@inertiajs/vue3';
import { markRaw } from 'vue';
import { icons } from '@/lib/icons';
import { IconName } from '@/enums/icons';
import { useUtils } from '@/composables/core/useUtils';
import { trans, trans_choice } from 'laravel-vue-i18n';

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
                bio: 'Software'
            }
        },
        {
            id: '2',
            type: 'tenant',
            attributes: {
                name: 'Penstej Systems',
                logo: markRaw(AppLogo),
                bio: 'Elite Software'
            }
        }
    ];

    const menuOptions: Array<MenuItemInterface> = [
        {
            transChoiceKey: 'trans.dashboard',
            icon: icons[IconName.dashboard],
            url: route('dashboard'),
            show: isItTrue(can['view:dashboards'])
        },
        {
            transChoiceKey: 'trans.enrolment',
            icon: icons[IconName.user_add],
            url: route('dashboard'),
            show: isItTrue(can['view:enrolments'])
        },
        {
            transChoiceKey: 'trans.student',
            icon: icons[IconName.user_check],
            url: route('dashboard'),
            show: isItTrue(can['view:students'])
        },
        {
            transChoiceKey: 'trans.examination',
            icon: icons[IconName.book_check],
            url: route('dashboard'),
            show: isItTrue(can['view:examinations'])
        },
        {
            transChoiceKey: 'trans.accommodation',
            icon: icons[IconName.bed],
            url: route('dashboard'),
            show: isItTrue(can['view:accommodations'])
        },
        {
            transChoiceKey: 'trans.communication',
            url: '#',
            icon: icons[IconName.person_chat],
            show: isItTrue(can['view:communications'])
        },
        {
            transChoiceKey: 'trans.report',
            url: '#',
            icon: icons[IconName.report],
            show: isItTrue(can['view:reports'])
        },
        {
            transKey: 'trans.institution_setup',
            url: route('institution.index'),
            icon: icons[IconName.school],
            show: isItTrue(can['view:institution-settings'])
        },
        {
            transKey: 'trans.settings',
            url: route('settings.index'),
            icon: icons[IconName.cogs],
            show: isItTrue(can['view:settings'])
        },
        {
            transChoiceKey: 'trans.user',
            url: '#',
            icon: icons[IconName.users],
            show: isItTrue(can['view:users']),
        },
    ];

    const getTranslation = (item: any, keyIndex?: number) => {
        if (item?.transChoiceKey) {
            return trans_choice(item?.transChoiceKey, keyIndex ?? 2);
        }
        if (item?.transKey) {
            return trans(item?.transKey);
        }
        return item?.title;
    };
    return { menuOptions, tenants, getTranslation };
}

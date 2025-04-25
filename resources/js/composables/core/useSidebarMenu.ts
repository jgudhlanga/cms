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
				name: 'Havapol',
				logo: markRaw(AppLogo),
				bio: 'Software'
			}
		},
		{
			id: '2',
			type: 'tenant',
			attributes: {
				name: 'Wasomi Systems',
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
			transChoiceKey: 'trans.portfolio',
			icon: icons[IconName.folder],
			show: isItTrue(can['view:portfolios']),
			items: [
				{
					transChoiceKey: 'trans.client',
					url: route('portfolios.index'),
					show: isItTrue(can['view:portfolios'])
				},
				{
					transChoiceKey: 'trans.product_config',
					url: route('product-config-index'),
					show: isItTrue(can['view:product-settings'])
				},
				{
					transChoiceKey: 'trans.insurer',
					url: route('insurers.index'),
					show: isItTrue(can['view:insurers'])
				}
			]
		},
		{
			transChoiceKey: 'trans.billing',
			url: '#',
			icon: icons[IconName.money],
			show: isItTrue(can['view:billing'])
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
			transChoiceKey: 'trans.user',
			url: '#',
			icon: icons[IconName.users],
			show: isItTrue(can['view:users']),
			items: [
				{
					transKey: 'all_users',
					url: '#',
					show: isItTrue(can['view:users'])
				},
				{
					transKey: 'trans.create',
					url: '#',
					show: isItTrue(can['create:users'])
				}
			]
		},
		{
			transKey: 'trans.settings',
			url: route('settings.index'),
			icon: icons[IconName.settings],
			show: isItTrue(can['view:settings'])
		}
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

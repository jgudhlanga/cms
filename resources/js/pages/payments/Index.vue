<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import { usePaymentSettings } from '@/composables/payments/usePaymentSettings';
import { AuthObject } from '@/types/data-pagination';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ auth: AuthObject, errors: Object }>();
const can = props?.auth?.can;
const { tabs } = usePaymentSettings();
const breadcrumbs: Array<Link> = [
	{ transChoiceKey: 'settings', href: route('settings.index') },
	{ transChoiceKey: 'payment' }
];
</script>

<template>
	<Head :title="$tChoice('trans.payment', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<AvatarTitleList v-if="can['view:settings']" :tabs="tabs" />
		<BaseAlert
			v-else
			:description="$t('trans.forbidden_message')"
			:title="$t('trans.forbidden')"
		/>
	</PageContainer>
</template>

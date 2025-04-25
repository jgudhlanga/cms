<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useSettings } from '@/composables/settings/useSettings';
import { BreadcrumbItemInterface } from '@/types/ui';
import { AuthObject } from '@/types/data-pagination';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';

const props = defineProps<{ auth: AuthObject, errors: Object }>();
const { tabs } = useSettings();

const breadcrumbs: BreadcrumbItemInterface[] = [
	{ transChoiceKey: 'settings' }
];
const can = props?.auth?.can;

</script>

<template>
	<Head :title="$t('trans.settings')" />
	<PageContainer :breadcrumbs="breadcrumbs">
		<AvatarTitleList
			v-if="can['view:settings']"
			:tabs="tabs"
		/>
		<BaseAlert
			v-else
			:title="$t('trans.forbidden')"
			:description="$t('trans.forbidden_message')"
		/>
	</PageContainer>
</template>

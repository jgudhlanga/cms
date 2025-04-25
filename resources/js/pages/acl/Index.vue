<script setup lang="ts">

import { Head } from '@inertiajs/vue3';
import type { Link as LinkType } from '@/types/ui';
import { useAcl } from '@/composables/acl/useAcl';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { AuthObject } from '@/types/data-pagination';

const props = defineProps<{ auth: AuthObject, errors: object }>();

const { tabs } = useAcl();
const breadcrumbs: Array<LinkType> = [
	{ transChoiceKey: 'settings', href: route('settings.index') },
	{ transChoiceKey: 'acl' }
];
const can = props?.auth?.can;
</script>

<template>
	<Head :title="$tChoice('trans.acl', 2)" />
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

<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useAcl } from '@/composables/acl/useAcl';
import { useSettings } from '@/composables/settings/useSettings';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ auth: AuthObject; errors: object }>();
const { tabs } = useSettings();
const { tabs: aclTabs } = useAcl();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'settings' }];
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$t('trans.settings')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('trans.access_control_list')" :description="$t('trans.acl_settings_description')" />
        <AvatarTitleList v-if="can['view:settings']" :tabs="aclTabs" />
        <template v-if="can['view:settings']">
            <HeadingSmall :title="$tChoice('trans.dropdown', 2)" :description="$t('trans.general_settings_description')" class="mt-6" />
            <AvatarTitleList :tabs="tabs" />
        </template>
        <BaseAlert
            v-if="!can['view:settings']"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
        />
    </PageContainer>
</template>

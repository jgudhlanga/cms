<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AvatarTitleList from '@/components/core/util/AvatarTitleList.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useRbac } from '@/composables/rbac/useRbac';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ auth: AuthObject; errors: object }>();
const { tabs } = useRbac();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'trans.rbac' }];
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$t('trans.rbac')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('trans.rbac')" :description="$t('trans.rbac_settings_description')" />
        <AvatarTitleList v-if="can['view:settings']" :tabs="tabs" />
        <BaseAlert
            v-if="!can['view:settings']"
            :title="$t('trans.forbidden')"
            :description="$t('trans.forbidden_message')"
        />
    </PageContainer>
</template>

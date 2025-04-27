<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BreadcrumbItemInterface } from '@/types/ui';
import { AuthObject } from '@/types/data-pagination';
import PageContainer from '@/components/core/page/PageContainer.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';

const props = defineProps<{ auth: AuthObject; errors: object }>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'institution_setup' }];
const can = props?.auth?.can;
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseAlert :type="TypeVariant.info" :title="$t('trans.coming_soon')" :description="$t('trans.coming_soon_description')" />
    </PageContainer>
</template>

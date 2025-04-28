<script setup lang="ts">
import SettingsButton from '@/components/core/button/SettingsButton.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

defineProps<{ auth: AuthObject; errors: object }>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transKey: 'institution_setup' }];
</script>

<template>
    <Head :title="$t('trans.institution_setup')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('trans.administration')" :description="$t('trans.administration_description')" />
        <SettingsButton :title="$t('trans.manage')" />
        <HeadingSmall :title="$tChoice('trans.department', 2)" :description="$t('trans.department_description')" class="mt-6" />
        <SettingsButton :title="$t('trans.manage')" />
    </PageContainer>
</template>

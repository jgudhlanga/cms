<script setup lang="ts">
import SettingsButton from '@/components/core/button/SettingsButton.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

defineProps<{ auth: AuthObject; errors: object }>();

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'institution', transChoiceKeyIndex: 1 }];

const { navigateTo } = useUtils();
</script>

<template>
    <Head :title="$tChoice('trans.institution', 1)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall :title="$t('trans.administration')" :description="$t('trans.administration_description')" />
        <SettingsButton class="mt-2" :title="$t('trans.manage')" />
        <HeadingSmall :title="$tChoice('trans.department', 2)" :description="$t('trans.department_description')" class="mt-6" />
        <SettingsButton class="mt-2" @click="navigateTo(route('institution-departments.index'))" :title="$t('trans.manage')" />
        <HeadingSmall :title="$t('trans.application_form_config')" :description="$t('trans.application_description')" class="mt-6" />
        <SettingsButton class="mt-2" @click="navigateTo(route('applications.setup'))" :title="$t('trans.manage')" />
    </PageContainer>
</template>

<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import ComingSoon from '@/components/core/util/ComingSoon.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <ComingSoon />
    </PageContainer>
</template>

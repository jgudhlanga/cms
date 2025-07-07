<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div>Welcome</div>
    </PageContainer>
</template>

<script setup lang="ts">
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard', href: 'dashboard' }, { transChoiceKey: 'accommodation' }];
</script>

<template>
    <Head :title="$tChoice('trans.accommodation', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col">
            <ComingSoonAnimated />
        </div>
    </PageContainer>
</template>

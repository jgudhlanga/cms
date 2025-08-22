<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useUtils } from '@/composables/core/useUtils';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import 'vue3-search-select/Vue-Search-Select.css';
import ComingSoonAnimated from '@/components/core/util/ComingSoonAnimated.vue';

const breadcrumbs: BreadcrumbItemInterface[] = [{ transChoiceKey: 'dashboard' }];
const { navigateTo } = useUtils();

</script>
<template>
    <Head :title="$tChoice('trans.dashboard', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center justify-center">
            <BaseButton @click="() => navigateTo(route('generate-pdf', 1))">PDF</BaseButton>
        </div>
        <ComingSoonAnimated />
    </PageContainer>
</template>

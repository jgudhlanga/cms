<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import IdCardImportPanel from '@/pages/students/id-card-requests/partials/IdCardImportPanel.vue';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItemInterface[] = [
    { transChoiceKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'trans.student_id', href: route('admin.students.id-card-requests.index') },
    { transKey: 'trans.student_id_card_import_title' },
];
</script>

<template>
    <Head :title="$t('trans.student_id_card_import_title')" />

    <PageContainer
        :breadcrumbs="breadcrumbs"
        :back-url="route('admin.students.id-card-requests.index')"
        :hasBackNavigationLeading="true"
    >
        <template #backNavigationLeading>
            <div>
                <h2 class="text-lg font-semibold">{{ $t('trans.student_id_card_import_title') }}</h2>
            </div>
        </template>

        <IdCardImportPanel />
    </PageContainer>
</template>

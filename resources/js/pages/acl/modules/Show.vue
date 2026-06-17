<script setup lang="ts">
import { useModules } from '@/composables/acl/useModules';
import { Module } from '@/types/acl';
import { AuthObject } from '@/types/data-pagination';
import { Head } from '@inertiajs/vue3';

import ModuleSettingsForm from './partials/ModuleSettingsForm.vue';

const { showBreadcrumbs } = useModules();

defineProps<{
    module: Module;
    auth: AuthObject;
    errors: object;
}>();
</script>

<template>
    <Head :title="module.attributes.title" />
    <PageContainer :breadcrumbs="showBreadcrumbs(module)">
        <div class="max-w-2xl space-y-4">
            <div>
                <h1 class="text-lg font-medium">{{ module.attributes.title }}</h1>
                <p v-if="module.attributes.description" class="text-sm text-muted-foreground">
                    {{ module.attributes.description }}
                </p>
            </div>

            <ModuleSettingsForm :module="module" />
        </div>
    </PageContainer>
</template>

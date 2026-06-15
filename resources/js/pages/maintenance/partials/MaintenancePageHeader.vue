<script setup lang="ts">
import { trans } from 'laravel-vue-i18n';

defineProps<{
    sectionDescription?: string;
}>();
</script>

<template>
    <div class="space-y-1">
        <h1 class="text-lg font-semibold text-foreground">
            {{ trans('trans.maintenance') }}
        </h1>
        <p class="text-sm text-muted-foreground">
            {{ trans('trans.maintenance_description') }}
        </p>

        <div v-if="sectionDescription" class="pt-1">
            <p class="text-xs font-medium text-muted-foreground">
                {{ trans('trans.maintenance_about_section') }}
            </p>
            <p class="mt-0.5 text-xs leading-relaxed text-muted-foreground">
                {{ sectionDescription }}
            </p>
        </div>
    </div>
</template>

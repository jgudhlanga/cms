<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { BreadcrumbItemInterface } from '@/types/ui';

defineProps<{
    breadcrumb?: BreadcrumbItemInterface;
}>();

const { getTransFile } = useUtils();
</script>

<template>
    <span v-if="breadcrumb?.transChoiceKey != null">{{
        $tChoice(getTransFile(breadcrumb), breadcrumb?.transChoiceKeyIndex ?? 2)
    }}</span>
    <span v-else-if="breadcrumb?.transKey != null">{{ $t(getTransFile(breadcrumb)) }}</span>
    <span v-else>{{ breadcrumb?.title }}</span>
</template>

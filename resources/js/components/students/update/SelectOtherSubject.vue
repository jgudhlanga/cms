<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { SelectOption } from '@/types/utils';

interface Props {
    options: SelectOption[];
}

defineProps<Props>();
</script>

<template>
    <BaseSelect class="min-w-[250px]" label="" v-bind="$attrs" placeholder="search subject..." :options="options" :is-searchable="true" :is-clearable="false" />
</template>

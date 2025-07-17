<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { EXAM_SITTINGS } from '@/lib/constants';
import { ref } from 'vue';

const options = ref(EXAM_SITTINGS);
</script>

<template>
    <BaseSelect class="w-[200px]" label="" v-bind="$attrs" placeholder="" :options="options" :is-searchable="false" :is-clearable="false" />
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    type?: 'single' | 'multiple';
    defaultValue?: string | string[];
    modelValue?: string | string[];
    collapsible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'multiple',
    defaultValue: () => [],
    collapsible: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | string[]];
}>();

const isControlled = computed(() => props.modelValue !== undefined);

const accordionValue = computed({
    get: () => props.modelValue ?? (props.type === 'single' ? '' : []),
    set: (value: string | string[]) => emit('update:modelValue', value),
});
</script>

<template>
    <Accordion
        v-if="isControlled"
        v-model="accordionValue"
        :type="type"
        :collapsible="collapsible"
        class="flex w-full flex-col gap-3"
    >
        <slot />
    </Accordion>
    <Accordion
        v-else
        :type="type"
        :collapsible="collapsible"
        class="flex w-full flex-col gap-3"
        :default-value="defaultValue"
    >
        <slot />
    </Accordion>
</template>

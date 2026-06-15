<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    defaultValue?: string[];
    modelValue?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    defaultValue: () => [],
});

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const isControlled = computed(() => props.modelValue !== undefined);

const accordionValue = computed({
    get: () => props.modelValue ?? [],
    set: (value: string[]) => emit('update:modelValue', value),
});
</script>

<template>
    <Accordion
        v-if="isControlled"
        v-model="accordionValue"
        type="multiple"
        collapsible
        class="flex w-full flex-col gap-3"
    >
        <slot />
    </Accordion>
    <Accordion
        v-else
        type="multiple"
        collapsible
        class="flex w-full flex-col gap-3"
        :default-value="defaultValue"
    >
        <slot />
    </Accordion>
</template>

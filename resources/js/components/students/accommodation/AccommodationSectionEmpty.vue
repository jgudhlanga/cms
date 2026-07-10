<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { Component } from 'vue';

interface Props {
    icon: Component;
    message: string;
    actionLabel?: string | null;
}

withDefaults(defineProps<Props>(), {
    actionLabel: null,
});

const emit = defineEmits<{
    action: [];
}>();
</script>

<template>
    <div class="rounded-xl border border-dashed border-border bg-muted/10 px-4 py-10 text-center">
        <component
            :is="icon"
            class="mx-auto mb-3 h-12 w-12 text-muted-foreground/40"
            aria-hidden="true"
        />
        <p class="text-sm text-muted-foreground">{{ message }}</p>
        <BaseButton
            v-if="actionLabel"
            class="mt-4"
            :color="ColorVariant.primary"
            :size="ButtonSize.sm"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </BaseButton>
    </div>
</template>

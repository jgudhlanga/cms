<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';

withDefaults(
    defineProps<{
        primaryLabel: string;
        processing?: boolean;
        errorHint?: string | null;
        showBack?: boolean;
        primaryDisabled?: boolean;
    }>(),
    {
        showBack: false,
        primaryDisabled: false,
    },
);

const emit = defineEmits<{
    primary: [];
    back: [];
}>();
</script>

<template>
    <div
        class="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-background/95 pt-2 shadow-[0_-4px_12px_rgba(0,0,0,0.08)] backdrop-blur-sm pb-[max(0.5rem,env(safe-area-inset-bottom))]"
    >
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-0 lg:max-w-3xl">
            <p v-if="errorHint" class="mb-1.5 text-center text-xs text-destructive" role="alert">
                {{ errorHint }}
            </p>
            <div class="flex items-center justify-between gap-2">
                <BaseButton
                    v-if="showBack"
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.shade"
                    @click="emit('back')"
                >
                    {{ $t('trans.back') }}
                </BaseButton>
                <div v-else class="shrink-0" aria-hidden="true" />
                <BaseButton
                    type="button"
                    :size="ButtonSize.sm"
                    :variant="ColorVariant.primary"
                    :processing="processing"
                    :disabled="primaryDisabled"
                    @click="emit('primary')"
                >
                    {{ primaryLabel }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>

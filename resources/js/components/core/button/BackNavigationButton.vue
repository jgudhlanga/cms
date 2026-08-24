<script setup lang="ts">
import { computed } from 'vue';
import { trans, transChoice } from 'laravel-vue-i18n';

import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import type { Link } from '@/types/ui';

const props = defineProps<{
    url: string;
    destination?: Link;
    /** Match pill-style header actions (e.g. Applicant Lookup). */
    pill?: boolean;
}>();

const { getTransFile } = useUtils();

const buttonTitle = computed((): string => {
    if (!props.destination) {
        return transChoice('trans.back', 1);
    }

    const destinationName =
        props.destination.transChoiceKey != null
            ? transChoice(getTransFile(props.destination), props.destination.transChoiceKeyIndex ?? 2)
            : props.destination.transKey != null
              ? trans(getTransFile(props.destination))
              : (props.destination.title ?? '');

    return trans('trans.back_to', { destination: destinationName });
});
</script>

<template>
    <button
        v-if="pill"
        type="button"
        class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold hover:bg-muted"
        @click="() => $inertia.visit(url)"
    >
        <BaseIcon :name="IconName.back" class="h-3.5 w-3.5 shrink-0" />
        {{ buttonTitle }}
    </button>
    <BaseButton v-else :title="buttonTitle" :size="ButtonSize.sm" :variant="ColorVariant.shade" @click="() => $inertia.visit(url)">
        <BaseIcon :name="IconName.back" :color="ColorVariant.shade" />
    </BaseButton>
</template>

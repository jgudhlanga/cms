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
    <BaseButton :title="buttonTitle" :size="ButtonSize.sm" :variant="ColorVariant.shade" @click="() => $inertia.visit(url)">
        <BaseIcon :name="IconName.back" :color="ColorVariant.shade" />
    </BaseButton>
</template>

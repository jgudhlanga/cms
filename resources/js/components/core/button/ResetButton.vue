<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { ButtonSize } from '@/enums/buttons';

withDefaults(
    defineProps<{
        size?: ButtonSize;
    }>(),
    {
        size: ButtonSize.md,
    },
);
</script>

<template>
    <BaseButton
        type="button"
        :variant="ColorVariant.shade"
        v-bind="$attrs"
        class="rounded-full"
        :size="size"
    >
        <component :is="icons[IconName.reset]" class="h-4 w-4" />
        {{ $t('students.reset') }}
    </BaseButton>
</template>

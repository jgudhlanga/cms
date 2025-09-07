<script setup lang="ts">
import { BaseButton } from '@/components/core/button/index';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';

interface Props {
    action: () => void;
}

defineProps<Props>();
</script>

<template>
    <BaseButton
        @click="() => action()"
        :title="$t('trans.edit')"
        :size="ButtonSize.sm"
        :variant="ColorVariant.primary_outline"
        classes="rounded-full"
    >
        <component :is="icons[IconName.edit]" />
    </BaseButton>
</template>

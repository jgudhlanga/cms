<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { ButtonSize } from '@/enums/buttons';

</script>

<template>
    <BaseButton
    :variant="ColorVariant.shade"
    v-bind="$attrs"
    class="rounded-full"
    :size="ButtonSize.md"
    >
        <component :is="icons[IconName.reset]" class="h-4 w-4" />
        {{ $t('hms.filter_reset') }}
    </BaseButton>
</template>
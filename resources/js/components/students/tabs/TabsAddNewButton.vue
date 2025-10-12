<script setup lang="ts">
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import BaseButton from '../../core/button/BaseButton.vue';

interface Props {
    action: () => void;
}
defineProps<Props>();
</script>

<template>
    <BaseButton classes="rounded-full" :variant="ColorVariant.primary_outline" @click="action">
        <BaseIcon :name="IconName.add" />
        <span>{{ $t('trans.add_new') }}</span>
    </BaseButton>
</template>

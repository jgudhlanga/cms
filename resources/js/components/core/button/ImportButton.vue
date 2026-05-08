<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
</script>

<template>
    <BaseButton type="button" :variant="ColorVariant.primary_outline">
        <BaseIcon :name="IconName.import" :color="ColorVariant.primary" />
        <span>{{ $t('trans.import') }}</span>
    </BaseButton>
</template>

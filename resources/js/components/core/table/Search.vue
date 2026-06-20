<script setup lang="ts">
import { BaseInputWithIcon } from '@/components/core/form';
import { IconName } from '@/enums/icons';
</script>

<template>
    <BaseInputWithIcon :icon="IconName.search" :placeholder="`${$t('trans.search')}`" class="rounded-full" />
</template>

<script setup lang="ts">
import { BreadcrumbItemInterface } from '@/types/ui';

defineProps<{
	breadcrumb?: BreadcrumbItemInterface;
}>();

</script>

<template>
	<span v-if="breadcrumb?.transChoiceKey != null">{{ $tChoice('trans.' + breadcrumb?.transChoiceKey, 2) }}</span>
	<span v-else-if="breadcrumb?.transKey != null">{{ $t('trans.' + breadcrumb?.transKey) }}</span>
	<span v-else>{{ breadcrumb?.title }}</span>
</template>

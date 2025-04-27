<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

const model = defineModel<boolean>();

defineProps<{
	inputId: string,
	onUpdate: (value: boolean) => void
	label?: string
}>();
</script>

<template>
	<div class="flex items-center space-x-2">
		<Switch :id="inputId" :model-value="model" @update:model-value="onUpdate" />
		<Label v-if="label" :for="inputId">{{ label }}</Label>
	</div>
</template>

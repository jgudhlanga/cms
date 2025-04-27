<script setup lang="ts">
import BaseSwitch from './BaseSwitch.vue';

const model = defineModel<boolean>();

defineProps<{
	onUpdate: (value: boolean) => void
}>();
</script>

<template>
	<BaseSwitch
		input-id="still_studying"
		v-model="model"
		:label="$t('trans.rsa_citizen')"
		v-bind="$attrs"
		:on-update="onUpdate"
	/>
</template>

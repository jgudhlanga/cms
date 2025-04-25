<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="description"
		:label="$t('trans.description')"
		v-bind="$attrs"
	/>
</template>

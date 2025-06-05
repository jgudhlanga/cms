<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="passport_number"
		:label="$t('trans.passport_number')"
		v-bind="$attrs"
	/>
</template>

<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="trading_name"
		:label="$t('trans.trading_name')"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

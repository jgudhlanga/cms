<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="tax_reference_number"
		:label="$t('trans.tax_reference_number')"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

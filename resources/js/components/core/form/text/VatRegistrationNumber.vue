<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="vat_registration_number"
		:label="$t('trans.vat_registration_number')"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

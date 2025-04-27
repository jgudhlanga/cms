<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="company_registration_number"
		:label="$t('trans.company_registration_number')"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

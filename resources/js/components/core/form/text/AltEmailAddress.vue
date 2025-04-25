<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="alt_email_address"
		:label="$t('trans.alt_email_address')"
		v-bind="$attrs"
	/>
</template>

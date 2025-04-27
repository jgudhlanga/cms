<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="email_address"
		:label="$t('trans.email_address')"
		v-bind="$attrs"
	/>
</template>

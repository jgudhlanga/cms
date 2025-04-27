<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="passport_number"
		:label="$t('passportNumber')"
		v-bind="$attrs"
	/>
</template>

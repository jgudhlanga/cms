<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="id_number"
		:label="$t('idNumber')"
		v-bind="$attrs"
	/>
</template>

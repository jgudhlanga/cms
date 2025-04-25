<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="surname"
		:label="$t('surname')"
		v-bind="$attrs"
	/>
</template>

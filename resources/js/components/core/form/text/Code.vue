<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="code"
		:label="$tChoice('trans.code', 1)"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

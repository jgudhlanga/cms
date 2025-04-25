<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="scheme_number"
		:label="$tChoice('trans.scheme_number', 1)"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

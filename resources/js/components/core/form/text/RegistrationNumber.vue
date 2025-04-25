<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="registration_number"
		:label="$tChoice('trans.registration_number', 1)"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

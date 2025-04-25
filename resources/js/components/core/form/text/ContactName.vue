<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="contact_name"
		:label="$tChoice('trans.contact_name', 1)"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

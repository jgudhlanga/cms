<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="bank_branch_code"
		:label="$tChoice('trans.branch_code', 1)"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

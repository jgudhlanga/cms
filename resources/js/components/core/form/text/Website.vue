<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput
		input-id="website"
		:label="$t('trans.website')"
		v-bind="$attrs"
		placeholder=""
	/>
</template>

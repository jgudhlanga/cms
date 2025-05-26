<script setup lang="ts">
import BaseDatePicker from './BaseDatePicker.vue';
</script>

<template>
	<BaseDatePicker
		:label="$t('trans.date_of_birth')"
		v-bind="$attrs"
	/>
</template>

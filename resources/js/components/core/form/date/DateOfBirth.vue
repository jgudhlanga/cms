<script setup lang="ts">
import BaseDatePicker from './BaseDatePicker.vue';


const currentDate = new Date();
const sixteenYearsAgo = new Date(currentDate);
sixteenYearsAgo.setFullYear(currentDate.getFullYear() - 15);

</script>

<template>
	<BaseDatePicker
        input-id="date_of_birth"
		:label="$t('trans.date_of_birth')"
		v-bind="$attrs"
        :max-date="sixteenYearsAgo"
        :enable-time-picker="false"
	/>
</template>

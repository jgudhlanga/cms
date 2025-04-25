<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
	message?: string | object;
}>();

const errorMessage = computed(() => {
	if (
		typeof props.message === 'object' &&
		props.message !== null &&
		'_errors' in props.message &&
		Array.isArray((props.message as { _errors: unknown })._errors)
	) {
		const errorsArray = (props.message as { _errors: unknown[] })._errors;
		if (errorsArray.length > 0 && typeof errorsArray[0] === 'string') {
			return errorsArray[0]; // Return first error message
		}
	}
	return props.message;
});
</script>

<template>
	<div v-show="message">
		<p class="text-sm text-red-600 dark:text-red-500 font-extralight">
			{{ errorMessage }}
		</p>
	</div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/core/form/InputError.vue';
import { TextFieldType } from '@/enums/inputs';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
	inputId: string,
	label?: string,
	type?: TextFieldType,
	classes?: string,
	error?: string | object,
	inputAutoFocus?: boolean,

}>(), {
	type: TextFieldType.text
});

const baseClasses = 'px-2 py-2.5 focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0';

const setAutoFocus = () => {
	const inputElement = document.getElementById(props.inputId) as HTMLInputElement;
	if (inputElement) {
		props.inputAutoFocus && inputElement.focus();
	}
};

onMounted(() => setAutoFocus());

</script>

<template>
	<div class="flex flex-col">
		<div class="flex flex-col space-y-2">
			<Label :class="cn(error && 'text-destructive')" v-if="label" :for="inputId">{{ label }}</Label>
			<Input
				v-bind="$attrs"
				:id="inputId"
				:class="cn(baseClasses, classes)"
				:type="type"
			/>
		</div>
		<InputError class="lowercase" :message="error" />
	</div>
</template>

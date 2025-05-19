<script setup lang="ts">
import { onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/core/form/InputError.vue';
import { TextFieldType } from '@/enums/inputs';
import { cn } from '@/lib/utils';
interface Props {
    inputId: string,
    label?: string,
    type?: TextFieldType,
    classes?: string,
    error?: string | object,
    inputAutoFocus?: boolean,
    labelUppercase?: boolean,
    verticalLayout?: boolean,

}
const props = withDefaults(defineProps<Props>(), {
	type: TextFieldType.text,
	labelUppercase:false,
    verticalLayout:true,
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
		<div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-3')">
			<Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex items-center w-1/4')" v-if="label" :for="inputId">{{ label }}:</Label>
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

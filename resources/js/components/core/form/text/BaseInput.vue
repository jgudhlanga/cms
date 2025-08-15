<script setup lang="ts">
import { onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/core/form/InputError.vue';
import { TextFieldType } from '@/enums/inputs';
import { cn } from '@/lib/utils';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
interface Props {
    inputId: string,
    label?: string,
    type?: TextFieldType,
    classes?: string,
    error?: string | object,
    inputAutoFocus?: boolean,
    labelUppercase?: boolean,
    verticalLayout?: boolean,
    isRequired?: boolean,

}
const props = withDefaults(defineProps<Props>(), {
	type: TextFieldType.text,
	labelUppercase:false,
    verticalLayout:true,
    isRequired: false,
});

const baseClasses = 'px-3 py-4 focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0';

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
		<div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-2')">
			<Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex items-center w-1/4')" v-if="label" :for="inputId">
                {{ label }}<RequiredIndicator v-if="isRequired"/>
            </Label>
			<Input
				v-bind="$attrs"
				:id="inputId"
				:class="cn(baseClasses, classes)"
				:type="type"
			/>
		</div>
		<InputError :class="cn('flex w-full lowercase', !verticalLayout && 'justify-end')" :message="error" />
	</div>
</template>

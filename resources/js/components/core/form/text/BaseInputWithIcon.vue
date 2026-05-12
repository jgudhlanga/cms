<script setup lang="ts">
import { onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { TextFieldType } from '@/enums/inputs';
import { cn } from '@/lib/utils';
import { IconName, icons } from '@/lib/icons';

interface Props {
	icon: IconName
	type?: TextFieldType,
	classes?: string,
	error?: string | object,
	inputAutoFocus?: boolean,
	/** When true, the wrapper fills the parent (no max-width cap). */
	fullWidth?: boolean,
}

const props = withDefaults(defineProps<Props>(), {
	type: TextFieldType.text,
	fullWidth: false,
});

const baseClasses = 'px-2 py-1 focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0';

</script>
<template>
	<div
		:class="
			cn(
				'relative w-full items-center',
				props.fullWidth ? 'min-w-0 max-w-none' : 'max-w-sm',
			)
		"
	>
		<Input
			v-bind="$attrs"
			:class="cn(baseClasses, classes)"
			:type="type"
			class="pl-10" />
		<span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
			<component :is="icons[icon]" class="h-4 w-4 text-muted-foreground" />
		</span>
	</div>
</template>

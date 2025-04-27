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
}

const props = withDefaults(defineProps<Props>(), {
	type: TextFieldType.text
});

const baseClasses = 'px-2 py-1 focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0';

</script>
<template>
	<div class="relative w-full max-w-sm items-center">
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

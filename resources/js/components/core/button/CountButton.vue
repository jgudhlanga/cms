<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { buttonVariants, ButtonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ColorVariant } from '@/enums/colors';

const props = withDefaults(defineProps<{
	count: string,
	classes?: string;
	variant?: ColorVariant | ButtonVariants,
}>(), {
	variant: ColorVariant.primary
});


const extendedVariants: Record<ColorVariant, string> = {
	[ColorVariant.danger]: 'bg-red-500 text-white hover:bg-red-600',
	[ColorVariant.danger_outline]: 'bg-transparent border-[1px] border-red-500 text-red-500 hover:bg-red-300 hover:border-red-300',
	[ColorVariant.fuchsia]: 'bg-purple-500 text-white hover:bg-purple-600',
	[ColorVariant.fuchsia_outline]: 'bg-transparent border-[1px] border-purple-500 text-purple-500 hover:bg-purple-300 hover:border-purple-300',
	[ColorVariant.info]: 'bg-blue-500 text-white hover:bg-blue-600',
	[ColorVariant.info_outline]: 'bg-transparent border-[1px] border-blue-500 text-blue-500 hover:bg-blue-300 hover:border-blue-300',
	[ColorVariant.primary]: 'border border-transparent bg-primary text-primary-foreground hover:bg-primary/90',
	[ColorVariant.primary_outline]: 'border border-primary bg-transparent font-semibold text-primary hover:border-primary hover:bg-primary/20 hover:text-primary dark:hover:bg-primary/25',
	[ColorVariant.shade]: 'bg-accent-foreground text-white hover:bg-accent hover:text-accent-foreground',
	[ColorVariant.shade_outline]: 'bg-transparent border-[1px] border-secondary text-accent-foreground hover:bg-secondary hover:border-secondary',
	[ColorVariant.success]: 'bg-green-500 text-white hover:bg-green-600',
	[ColorVariant.success_outline]: 'bg-transparent border-[1px] border-green-500 text-green-500 hover:bg-green-300 hover:border-green-300',
	[ColorVariant.warning]: 'bg-amber-500 text-white hover:bg-amber-600',
	[ColorVariant.warning_outline]: 'bg-transparent border-[1px] border-amber-500 text-amber-500 hover:bg-amber-300 hover:border-amber-300',
	[ColorVariant.white]: '',
	[ColorVariant.transparent]: 'bg-transparent'
};


const computedClass = computed(() =>
	cn(
		buttonVariants({ variant: props.variant as any }),
		extendedVariants[props.variant as ColorVariant] || ''
	)
);

</script>

<template>
	<Button
		v-bind="$attrs"
		:class="cn(computedClass, props.classes, 'cursor-pointer')">
		{{ count }}
	</Button>
</template>

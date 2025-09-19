<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { buttonVariants, ButtonVariants } from '@/components/ui/button';
import { icons, IconName } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { ColorVariant } from '@/enums/colors';
import { ButtonSize } from '@/enums/buttons';

const props = withDefaults(defineProps<{
	classes?: string;
	processing?: boolean,
	variant?: ColorVariant | ButtonVariants,
	size?: ButtonSize | ButtonVariants,
	title?: string,
}>(), {
	processing: false,
	variant: ColorVariant.primary,
    size: ButtonSize.lg
});


const extendedVariants: Record<ColorVariant, string> = {
	[ColorVariant.danger]: 'bg-red-500 text-red-100 hover:bg-red-600',
	[ColorVariant.danger_outline]: 'bg-transparent border-[1px] border-red-500 text-red-500 hover:bg-red-300 hover:border-red-300',
	[ColorVariant.fuchsia]: 'bg-purple-500 text-purple-100 hover:bg-purple-600',
	[ColorVariant.fuchsia_outline]: 'bg-transparent border-[1px] border-purple-500 text-purple-500 hover:bg-purple-300 hover:border-purple-300',
	[ColorVariant.info]: 'bg-blue-500 text-blue-100 hover:bg-blue-600',
	[ColorVariant.info_outline]: 'bg-transparent border-[1px] border-blue-500 text-blue-500 hover:bg-blue-300 hover:border-blue-300',
	[ColorVariant.primary]: 'bg-persian-600 text-persian-100 hover:bg-persian-700 border border-transparent',
	[ColorVariant.primary_outline]: 'bg-transparent border-[1px] border-persian-600 text-persian-600 hover:bg-persian-200 hover:border-persian-200',
	[ColorVariant.shade]: 'bg-accent text-accent-foreground hover:bg-secondary hover:text-accent-foreground',
	[ColorVariant.shade_outline]: 'bg-transparent border-[1px] border-accent text-accent-foreground hover:bg-secondary hover:border-secondary',
	[ColorVariant.success]: 'bg-green-500 text-green-100 hover:bg-green-600',
	[ColorVariant.success_outline]: 'bg-transparent border-[1px] border-green-500 text-green-500 hover:bg-green-300 hover:border-green-300',
	[ColorVariant.warning]: 'bg-amber-500 text-amber-100 hover:bg-amber-600',
	[ColorVariant.warning_outline]: 'bg-transparent border-[1px] border-amber-500 text-amber-500 hover:bg-amber-300 hover:border-amber-300',
	[ColorVariant.white]: '',
	[ColorVariant.transparent]: 'bg-transparent'
};

const sizeVariants: Record<ButtonSize, string> = {
	[ButtonSize.default]: 'h-9 px-4 py-2 text-xs',
	[ButtonSize.xs]: 'h-6 px-2 text-xs',
	[ButtonSize.sm]: 'h-8 rounded-md px-3 text-xs',
	[ButtonSize.md]: 'h-10 px-8 text-sm',
	[ButtonSize.lg]: 'h-12 px-8 text-sm',
	[ButtonSize.xl]: 'h-13 px-8 text-lg'
};

const computedClass = computed(() =>
	cn(
		buttonVariants({ variant: props.variant as any }),
		buttonVariants({ size: props.size as any }),
		extendedVariants[props.variant as ColorVariant] || '',
		sizeVariants[props.size as ButtonSize] || ''
	)
);

</script>

<template>
	<Button
		v-bind="$attrs"
		:class="cn('uppercase rounded-md cursor-pointer', computedClass, props.classes)" :disabled="processing">
		<component :is="icons[IconName.loader]" v-if="processing" class="h-4 w-4 animate-spin" />
        <span v-if="title">{{ title }}</span>
		<slot />
	</Button>
</template>

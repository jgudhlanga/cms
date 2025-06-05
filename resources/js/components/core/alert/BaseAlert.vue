<script setup lang="ts">
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { icons, IconName } from '@/lib/icons';
import { TypeVariant } from '@/enums/type-variants';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{ description: string, type?: TypeVariant, title?: string }>(), {
	type: TypeVariant.warning
});

const variants: Record<TypeVariant, string> = {
	[TypeVariant.info]: 'border-persian-600/50 text-persian-600 dark:border-persian-600/50 [&>svg]:text-persian-600',
	[TypeVariant.warning]: 'border-amber-600/50 text-amber-600 dark:border-amber-600/50 [&>svg]:text-amber-600',
	[TypeVariant.danger]: 'border-red-600/50 text-red-600 dark:border-red-600/50 [&>svg]:text-red-600',
	[TypeVariant.success]: 'border-green-600/50 text-green-600 dark:border-green-600/50 [&>svg]:text-green-600',
	[TypeVariant.primary]: 'border-persian-600/50 text-persian-600 dark:border-persian-600/50 [&>svg]:text-persian-600',
};

const iconVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: IconName.info,
	[TypeVariant.warning]: IconName.warning,
	[TypeVariant.danger]: IconName.danger,
	[TypeVariant.success]: IconName.check_box,
	[TypeVariant.primary]: IconName.check_box
};

const computedClass = computed(() =>
	cn(
		'p-6',
		variants[props.type]
	));
</script>

<template>
	<Alert :class="computedClass">
		<component :is="icons[iconVariants[type] as IconName]" class="size-8" />
		<AlertTitle v-if="title" class="uppercase text-lg font-bold mx-4">{{ title }}</AlertTitle>
		<AlertDescription class="p-4 text-md">
			{{ description }}
		</AlertDescription>
	</Alert>
</template>

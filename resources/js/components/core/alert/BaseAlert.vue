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
	[TypeVariant.info]: 'border-picton-400/50 text-picton-400 dark:border-picton-400/50 [&>svg]:text-picton-400',
	[TypeVariant.warning]: 'border-amber-600/50 text-amber-600 dark:border-amber-600/50 [&>svg]:text-amber-600',
	[TypeVariant.danger]: 'border-red-600/50 text-red-600 dark:border-red-600/50 [&>svg]:text-red-600',
	[TypeVariant.success]: 'border-green-600/50 text-green-600 dark:border-green-600/50 [&>svg]:text-green-600'
};

const iconVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: IconName.info,
	[TypeVariant.warning]: IconName.warning,
	[TypeVariant.danger]: IconName.danger,
	[TypeVariant.success]: IconName.check_box
};

const computedClass = computed(() =>
	cn(
		'',
		variants[props.type]
	));
</script>

<template>
	<Alert :class="computedClass">
		<component :is="icons[iconVariants[type] as IconName]" class="h-4 w-4" />
		<AlertTitle v-if="title">{{ title }}</AlertTitle>
		<AlertDescription>
			{{ description }}
		</AlertDescription>
	</Alert>
</template>

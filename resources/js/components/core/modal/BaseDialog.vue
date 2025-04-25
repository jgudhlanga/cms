<script setup lang="ts">
import { computed } from 'vue';
import { SizeVariant } from '@/enums/sizes';
import { BaseButton } from '../button';
import { ColorVariant } from '@/enums/colors';
import { cn } from '@/lib/utils';
import { IconName, icons } from '@/lib/icons';
import { TypeVariant } from '@/enums/type-variants';
import { trans } from 'laravel-vue-i18n';

const props = withDefaults(defineProps<{
	type: TypeVariant,
	message: string,
	title?: string,
	clickToClose?: boolean,
	escToClose?: boolean,
	onConfirm?: Function,
	onBeforeOpen?: Function,
	onOpened?: Function,
	onBeforeClose?: Function,
	onClosed?: Function,
	size?: SizeVariant,
	confirmBtnText?: string,
	processing?: boolean,
}>(), {
	clickToClose: true,
	escToClose: true,
	size: SizeVariant.sm,
	confirmBtnText: () => trans('trans.okay'),
	processing: false
});

const baseClasses = 'rounded-2xl shadow-lg  m-2';

const typeVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: 'bg-white text-picton-400',
	[TypeVariant.warning]: 'bg-white text-amber-600',
	[TypeVariant.danger]: 'bg-white text-red-600',
	[TypeVariant.success]: 'bg-white text-green-600'
};

const uiVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: 'border-picton-200 justify-center',
	[TypeVariant.warning]: 'border-amber-200 justify-end',
	[TypeVariant.danger]: 'border-red-200 justify-end',
	[TypeVariant.success]: 'border-green-200 justify-center'
};

const closeButtonVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: 'hover:bg-picton-200',
	[TypeVariant.warning]: 'hover:bg-amber-200',
	[TypeVariant.danger]: 'hover:bg-red-200',
	[TypeVariant.success]: 'hover:bg-green-200'
};

const modalVariants: Record<SizeVariant, string> = {
	[SizeVariant.xs]: 'w-96',
	[SizeVariant.sm]: 'w-[640px]',
	[SizeVariant.md]: 'w-[768px]',
	[SizeVariant.lg]: 'w-[1024px]',
	[SizeVariant.xl]: 'w-[1280px]',
	[SizeVariant.full]: 'w-full h-screen rounded-none'
};

const computedClass = computed(() =>
	cn(
		typeVariants[props.type],
		baseClasses,
		modalVariants[props.size]
	)
);

const confrimButtonVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: ColorVariant.primary,
	[TypeVariant.warning]: ColorVariant.warning,
	[TypeVariant.danger]: ColorVariant.danger,
	[TypeVariant.success]: ColorVariant.success
};

const iconVariants: Record<TypeVariant, string> = {
	[TypeVariant.info]: IconName.info,
	[TypeVariant.warning]: IconName.warning,
	[TypeVariant.danger]: IconName.danger,
	[TypeVariant.success]: IconName.check_box
};
</script>

<template>
	<div class="fixed z-50 inset-0 flex items-center justify-center bg-black bg-opacity-50">
		<!-- Modal Container -->
		<div :class="computedClass">
			<!-- Modal Header -->
			<div class="flex justify-between items-center px-6 pt-6">
				<div class="flex space-x-3 items-center">
					<component :is="icons[iconVariants[type] as IconName]" :size="22" />
					<h2 class="text-md font-semibold uppercase">{{ title }}</h2>
				</div>
				<button :class="cn('p-2 rounded-full', closeButtonVariants[type])" @click="() => onClosed!() ?? null">
					<component :is="icons[IconName.close]" :size="26" />
				</button>
			</div>
			<div :class="cn('w-full h-1 border-b-[1px] my-2', uiVariants[type] ?? '')"></div>
			<!-- Modal Body -->
			<div class="px-6 pt-3">
				<slot />
			</div>

			<!-- Modal Footer -->
			<div :class="cn('mt-6 flex w-full border-t-[1px] px-6 py-5 space-x-3', uiVariants[type])">
				<BaseButton v-if="type === TypeVariant.danger || type === TypeVariant.warning"
				            :variant="ColorVariant.shade" @click="() => onClosed!() ?? null">
					{{ $t('trans.close') }}
				</BaseButton>
				<BaseButton :variant="confrimButtonVariants[type] as ColorVariant" @click="() => onConfirm!() ?? null"
				            :processing="processing">
					{{ confirmBtnText }}
				</BaseButton>
			</div>
		</div>
	</div>
</template>

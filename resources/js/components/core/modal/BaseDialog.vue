<script setup lang="ts">
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { TypeVariant } from '@/enums/type-variants';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { BaseButton } from '../button';

const props = withDefaults(
    defineProps<{
        type: TypeVariant;
        message: string;
        title?: string;
        clickToClose?: boolean;
        escToClose?: boolean;
        onConfirm?: () => void;
        onBeforeOpen?: () => void;
        onOpened?: () => void;
        onBeforeClose?: () => void;
        onClosed?: () => void;
        size?: SizeVariant;
        confirmBtnText?: string;
        processing?: boolean;
    }>(),
    {
        clickToClose: true,
        escToClose: true,
        size: SizeVariant.sm,
        confirmBtnText: () => trans('trans.continue'),
        processing: false,
    },
);

const baseClasses = 'rounded-2xl shadow-lg  m-2';

const typeVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'bg-white text-persian-600',
    [TypeVariant.primary]: 'bg-white text-persian-600',
    [TypeVariant.warning]: 'bg-white text-amber-600',
    [TypeVariant.danger]: 'bg-white text-red-600',
    [TypeVariant.success]: 'bg-white text-green-600',
};

const uiVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'border-persian-600',
    [TypeVariant.primary]: 'border-persian-600',
    [TypeVariant.warning]: 'border-amber-600',
    [TypeVariant.danger]: 'border-red-600',
    [TypeVariant.success]: 'border-green-600',
};

const closeButtonVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: 'hover:bg-persian-200',
    [TypeVariant.primary]: 'hover:bg-persian-200',
    [TypeVariant.warning]: 'hover:bg-amber-200',
    [TypeVariant.danger]: 'hover:bg-red-200',
    [TypeVariant.success]: 'hover:bg-green-200',
};

const modalVariants: Record<SizeVariant, string> = {
    [SizeVariant.xs]: 'w-96',
    [SizeVariant.sm]: 'w-[640px]',
    [SizeVariant.md]: 'w-[768px]',
    [SizeVariant.lg]: 'w-[1024px]',
    [SizeVariant.xl]: 'w-[1280px]',
    [SizeVariant.full]: 'w-full h-screen rounded-none',
};

const computedClass = computed(() => cn(typeVariants[props.type], baseClasses, modalVariants[props.size]));

const confirmButtonVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: ColorVariant.primary,
    [TypeVariant.primary]: ColorVariant.primary,
    [TypeVariant.warning]: ColorVariant.warning,
    [TypeVariant.danger]: ColorVariant.danger,
    [TypeVariant.success]: ColorVariant.success,
};

const iconVariants: Record<TypeVariant, string> = {
    [TypeVariant.info]: IconName.info,
    [TypeVariant.primary]: IconName.info,
    [TypeVariant.warning]: IconName.warning,
    [TypeVariant.danger]: IconName.danger,
    [TypeVariant.success]: IconName.check_box,
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
        <!-- Modal Container -->
        <div :class="computedClass" class="relative z-10">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 pt-6">
                <div class="flex items-center space-x-3">
                    <component :is="icons[iconVariants[type] as IconName]" :size="22" />
                    <h2 class="text-md font-semibold uppercase">{{ title }}</h2>
                </div>
                <button :class="cn('rounded-full p-2', closeButtonVariants[type])" @click="() => (onClosed ? onClosed() : null)">
                    <component :is="icons[IconName.close]" :size="26" />
                </button>
            </div>
            <div :class="cn('my-2 h-1 w-full')"></div>
            <!-- Modal Body -->
            <div class="px-6 pt-3">
                <div class="flex w-full space-x-3 rounded-md border-l-4 bg-gray-50 p-3 shadow-sm" :class="uiVariants[type]">
                    <slot />
                </div>
            </div>
            <!-- Modal Footer -->
            <div :class="cn('mt-6 flex w-full items-center justify-center space-x-3 px-6 py-5')">
                <BaseButton
                    classes="rounded-full"
                    v-if="type === TypeVariant.danger || type === TypeVariant.warning"
                    :variant="ColorVariant.shade"
                    @click="() => (onClosed ? onClosed() : null)"
                >
                    {{ $t('trans.close') }}
                </BaseButton>
                <BaseButton
                    classes="rounded-full"
                    :variant="confirmButtonVariants[type] as ColorVariant"
                    @click="() => (onConfirm ? onConfirm() : null)"
                    :processing="processing"
                >
                    {{ confirmBtnText }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>

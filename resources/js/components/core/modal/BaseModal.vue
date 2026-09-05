<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { useModalStore } from '@/store/core/useModalStore';
import { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title: string;
    name: string;
    hasForm?: boolean;
    escToClose?: boolean;
    size?: SizeVariant;
    cancelBtnText?: string;
    actionBtnText?: string;
    showActionButton?: boolean;
    onFormAction?: () => void;
    onCloseModal?: () => void;
    form?: InertiaForm<any>;
    stackFooterOnMobile?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    hasForm: true,
    escToClose: false,
    size: SizeVariant.md,
    cancelBtnText: 'trans.close',
    actionBtnText: 'trans.save',
    showActionButton: true,
    stackFooterOnMobile: false,
});

const isFullPage = computed(() => props.size === SizeVariant.full);
const overlayClass = computed(() =>
    cn('fixed inset-0 z-20 flex items-center justify-center', isFullPage.value ? 'p-0' : 'p-3'),
);
const baseClasses = 'max-w-full bg-background rounded-2xl shadow-lg overflow-x-hidden outline-hidden p-3';
const modalVariants: Record<SizeVariant, string> = {
    [SizeVariant.xs]: 'w-96',
    [SizeVariant.sm]: 'w-[640px]',
    [SizeVariant.md]: 'w-[768px]',
    [SizeVariant.lg]: 'w-[1360px]',
    [SizeVariant.xl]: 'w-[1600px]',
    [SizeVariant.full]: 'shadow-none w-full h-full max-h-none rounded-none p-0 flex flex-col overflow-hidden',
};
const computedClass = computed(() =>
    cn(isFullPage.value ? 'overflow-hidden' : 'max-h-[90vh] overflow-y-auto', baseClasses, modalVariants[props.size]),
);
const formClass = computed(() => cn('flex flex-col', isFullPage.value ? 'min-h-0 flex-1' : ''));
const bodyClass = computed(() =>
    cn(
        'flex-1 px-6 py-4',
        isFullPage.value ? 'flex min-h-0 flex-col overflow-hidden' : 'space-y-6 overflow-y-auto',
    ),
);
const footerClasses = computed(() =>
    cn(
        'mt-auto flex w-full shrink-0 border-t-[1px] px-6 py-5',
        props.stackFooterOnMobile
            ? 'flex-col-reverse gap-2 sm:flex-row sm:justify-center sm:gap-0 sm:space-x-3'
            : 'justify-center space-x-3',
    ),
);
const footerButtonClasses = computed(() => (props.stackFooterOnMobile ? 'w-full sm:w-auto' : ''));
const { isOpen, closeModal } = useModalStore();

const destroyModal = () => {
    if (props.onCloseModal !== undefined) {
        props.onCloseModal!();
    }
    closeModal(props.name);
    if (props.form) {
        props.form.clearErrors();
    }
};
</script>

<template>
    <Transition name="fade">
        <div v-if="isOpen(name)" :class="overlayClass">
            <!-- Backdrop -->
            <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
            <!-- Modal Container -->
            <div :class="computedClass" class="relative z-10">
                <!-- Modal Header -->
                <div class="flex shrink-0 items-center justify-between px-6 pt-6">
                    <h2 class="text-md font-semibold uppercase">{{ title }}</h2>
                    <button class="hover:bg-accent rounded-full p-2" @click="() => destroyModal()">
                        <component :is="icons[IconName.close]" color="black" :size="26" />
                    </button>
                </div>
                <div class="my-2 h-1 w-full shrink-0 border-b-[1px]"></div>
                <form :name="name" v-if="hasForm" @submit.prevent="() => (onFormAction ? onFormAction!() : null)" :class="formClass">
                    <!-- Modal Body -->
                    <div :class="bodyClass">
                        <slot name="body" />
                    </div>
                    <!-- Modal Footer -->
                    <div :class="footerClasses">
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.shade"
                            :classes="footerButtonClasses"
                            @click="() => destroyModal()"
                            :size="ButtonSize.lg"
                        >
                            {{ $t(cancelBtnText) }}
                        </BaseButton>
                        <BaseButton
                            v-if="showActionButton"
                            :processing="form?.processing"
                            :disabled="form?.processing"
                            :classes="footerButtonClasses"
                            :size="ButtonSize.lg"
                        >
                            {{ $t(actionBtnText) }}
                        </BaseButton>
                        <slot name="action-button" />
                    </div>
                </form>
                <div v-else class="flex flex-col">
                    <div class="relative flex-1 space-y-6 overflow-y-auto px-6 py-4">
                        <slot />
                    </div>
                    <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
                        <BaseButton type="button" :variant="ColorVariant.shade" @click="() => destroyModal()" :size="ButtonSize.lg">
                            {{ $t(cancelBtnText) }}
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

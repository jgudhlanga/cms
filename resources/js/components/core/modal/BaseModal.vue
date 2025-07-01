<script setup lang="ts">
import { computed } from 'vue';
import { InertiaForm } from '@inertiajs/vue3';
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { useModalStore } from '@/store/core/useModalStore';
import { ButtonSize } from '@/enums/buttons';

interface Props {
    title: string;
    name: string;
    hasForm?: boolean;
    escToClose?: boolean;
    size?: SizeVariant;
    cancelBtnText?: string;
    actionBtnText?: string;
    onFormAction?: () => void;
    onCloseModal?: () => void;
    form?: InertiaForm<any>;
}

const props = withDefaults(defineProps<Props>(), {
    hasForm: true,
    escToClose: false,
    size: SizeVariant.md,
    cancelBtnText: 'trans.close',
    actionBtnText: 'trans.save',
});

const baseClasses = 'bg-background rounded-2xl shadow-lg overflow-y-auto overflow-x-hidden outline-hidden';
const modalVariants: Record<SizeVariant, string> = {
    [SizeVariant.xs]: 'w-96',
    [SizeVariant.sm]: 'w-[640px]',
    [SizeVariant.md]: 'w-[768px]',
    [SizeVariant.lg]: 'w-[1360px]',
    [SizeVariant.xl]: 'w-[1600px]',
    [SizeVariant.full]: 'shadow-none w-full h-full rounded-none',
};
const computedClass = computed(() => cn(baseClasses, modalVariants[props.size]));
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
        <div v-if="isOpen(name)" class="fixed inset-0 z-20 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 z-0 bg-black opacity-50"></div>
            <!-- Modal Container -->
            <div :class="computedClass" class="relative z-10">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 pt-6">
                    <h2 class="text-md font-semibold uppercase">{{ title }}</h2>
                    <button class="hover:bg-accent rounded-full p-2" @click="() => destroyModal()">
                        <component :is="icons[IconName.close]" color="black" :size="26" />
                    </button>
                </div>
                <div class="my-2 h-1 w-full border-b-[1px]"></div>
                <form :name="name" v-if="hasForm" @submit.prevent="() => (onFormAction ? onFormAction!() : null)" class="flex flex-col">
                    <!-- Modal Body -->
                    <div class="flex-auto space-y-6 overflow-visible px-6 py-4">
                        <slot name="body" />
                    </div>
                    <!-- Modal Footer -->
                    <div class="mt-6 flex w-full justify-center space-x-3 border-t-[1px] px-6 py-5">
                        <BaseButton type="button" :variant="ColorVariant.shade" @click="() => destroyModal()" :size="ButtonSize.lg">
                            {{ $t(cancelBtnText) }}
                        </BaseButton>
                        <BaseButton :processing="form.processing" :disabled="form.processing" :size="ButtonSize.lg">
                            {{ $t(actionBtnText) }}
                        </BaseButton>
                        <slot name="action-button" />
                    </div>
                </form>
                <div v-else class="flex flex-col">
                    <div class="relative flex-auto space-y-6 overflow-visible px-6 py-4">
                        <slot />
                    </div>
                    <div class="mt-6 flex w-full justify-end space-x-3 border-t-[1px] px-6 py-5">
                        <BaseButton type="button" :variant="ColorVariant.shade" @click="() => destroyModal()" :size="ButtonSize.lg">
                            {{ $t(cancelBtnText) }}
                        </BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

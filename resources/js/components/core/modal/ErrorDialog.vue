<script lang="ts" setup>
import { BaseButton } from '@/components/core/button';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/lib/icons';
import { useErrorDialog } from '@/composables/core/useErrorDialog';

const { isVisible, options, confirm, close } = useErrorDialog();
</script>

<template>
    <!-- Backdrop -->
    <div v-if="isVisible" class="absolute inset-0 z-0 bg-black opacity-50"></div>
    <Transition name="fade">
        <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center p-3">
            <Transition name="scale">
                <div v-if="isVisible" class="w-full max-w-md rounded-lg bg-white shadow-xl transition-all">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="mb-6 flex items-center space-x-2">
                            <div class="text-red-500">
                                <BaseIcon :name="IconName.warning" />
                            </div>
                            <div class="text-sm font-semibold uppercase">
                                {{ options.title }}
                            </div>
                        </div>

                        <!-- Message -->
                        <p class="mb-4 text-gray-600">{{ options.message }}</p>

                        <!-- Optional Note -->
                        <div v-if="options.note" class="mb-4 rounded-md border border-red-200 bg-red-50 p-3">
                            <p class="text-sm text-red-800"><strong>Note:</strong> {{ options.note }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex justify-center space-x-3">
                            <BaseButton
                                v-if="options.showCancelBtn"
                                @click="close"
                                :title="options.cancelText"
                                :variant="ColorVariant.shade"
                                :size="ButtonSize.lg"
                                classes="rounded-full"
                            ></BaseButton>
                            <BaseButton
                                :variant="ColorVariant.danger"
                                @click="confirm"
                                :title="options.confirmText"
                                :size="ButtonSize.lg"
                                classes="rounded-full"
                            ></BaseButton>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
/* Fade transition for overlay */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Scale transition for dialog box */
.scale-enter-active,
.scale-leave-active {
    transition:
        transform 0.25s ease,
        opacity 0.25s ease;
}
.scale-enter-from {
    transform: scale(0.95);
    opacity: 0;
}
.scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>

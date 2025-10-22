<script setup lang="ts">
import { IconName, icons } from '@/lib/icons';
import { onMounted, onUnmounted } from 'vue';

interface Props {
    show: boolean;
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    title?: string;
    closeManually?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    size: 'md',
    closeManually: false,
});

const emit = defineEmits(['close']);

function close() {
    emit('close');
}

function closeOnEscape($event: any) {
    if (!props.show) {
        return;
    }

    if ($event.key !== 'Escape') {
        return;
    }

    const focusedElementName = $event.target.nodeName;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(focusedElementName)) {
        return;
    }
    close();
}

if (!props.closeManually) {
    onMounted(() => document.addEventListener('keydown', closeOnEscape));
    onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
}
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-300">
            <div v-show="show" class="fixed inset-0 z-50 flex size-full items-center justify-center">
                <transition
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    enter-active-class="transition duration-300"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                    leave-active-class="transition duration-300"
                >
                    <div v-show="show" class="fixed inset-0 z-40 size-full bg-black/75" @click="closeManually ? null : close()" />
                </transition>
                <transition
                    enter-from-class="opacity-0 scale-90"
                    enter-to-class="opacity-100 scale-100"
                    enter-active-class="transition duration-300"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-90"
                    leave-active-class="transition duration-300"
                >
                    <div
                        v-show="show"
                        class="z-50 w-full rounded-lg bg-white p-6"
                        :class="{
                            'max-w-sm': size === 'sm',
                            'max-w-md': size === 'md',
                            'max-w-lg': size === 'lg',
                            'max-w-xl': size === 'xl',
                            'size-full rounded-none': size === 'full',
                        }"
                    >
                        <slot name="title">
                            <div class="flex items-center justify-between p-2">
                                <div class="text-lg font-semibold uppercase">{{ title }}</div>
                                <div>
                                    <button class="hover:bg-accent rounded-full p-2" @click="close">
                                        <component :is="icons[IconName.close]" color="black" :size="26" />
                                    </button>
                                </div>
                            </div>
                        </slot>
                        <slot />
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

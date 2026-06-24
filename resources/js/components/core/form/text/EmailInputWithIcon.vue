<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { Mail } from 'lucide-vue-next';
import { onMounted } from 'vue';

interface Props {
    inputId?: string;
    label?: string;
    error?: string | object;
    inputAutoFocus?: boolean;
    labelUppercase?: boolean;
    isRequired?: boolean;
    modelValue?: string;
    placeholder?: string;
    tabindex?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    inputId: 'email',
    labelUppercase: false,
    isRequired: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input'): void;
}>();

const modelValue = useVModel(props, 'modelValue', emit);

const inputClasses = `
  min-h-11
  border border-input
  bg-background/80 text-foreground
  placeholder:text-muted-foreground
  rounded-xl
  pl-10
  focus-visible:outline-none
  focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-0
  disabled:cursor-not-allowed disabled:opacity-50
`;

const setAutoFocus = () => {
    const inputElement = document.getElementById(props.inputId) as HTMLInputElement | null;
    if (inputElement && props.inputAutoFocus) {
        inputElement.focus();
    }
};

onMounted(() => setAutoFocus());
</script>

<template>
    <div class="flex w-full flex-col gap-2">
        <Label
            v-if="label"
            :for="inputId"
            :class="cn('font-medium', error && 'text-destructive', labelUppercase && 'uppercase')"
        >
            {{ label }}<RequiredIndicator v-if="isRequired" />
        </Label>

        <div class="relative w-full">
            <Input
                :id="inputId"
                v-model="modelValue"
                type="email"
                :class="inputClasses"
                :placeholder="placeholder"
                :tabindex="tabindex"
                autocomplete="email"
                @input="emit('input')"
            />
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center px-3">
                <Mail class="size-4 text-muted-foreground" />
            </span>
        </div>

        <InputError :message="error" class="lowercase" />
    </div>
</template>

<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import PasswordStrengthIndicator from '@/components/core/form/PasswordStrengthIndicator.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { Eye, EyeOff } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Props {
    inputId: string;
    label?: string;
    classes?: string;
    error?: string | object;
    inputAutoFocus?: boolean;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
    modelValue?: string;
    placeholder?: string;
    showStrength?: boolean;
    autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
    showStrength: false,
    autocomplete: 'new-password',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input'): void;
}>();

const modelValue = useVModel(props, 'modelValue', emit);
const showPassword = ref(false);

const baseClasses = `
  p-3 pr-10
  border border-input
  bg-background text-foreground
  placeholder:text-muted-foreground
  rounded-md
  focus-visible:outline-none
  focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0
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
    <div class="flex w-full flex-col">
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-2')">
            <Label
                v-if="label"
                :for="inputId"
                :class="cn('font-medium', error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')"
            >
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>

            <div :class="cn('w-full', !verticalLayout && !label && 'flex-1', !verticalLayout && label && 'flex-1')">
                <div class="relative w-full">
                    <Input
                        :id="inputId"
                        v-model="modelValue"
                        :type="showPassword ? 'text' : 'password'"
                        :class="cn(baseClasses, classes)"
                        :placeholder="placeholder"
                        :autocomplete="autocomplete"
                        @input="emit('input')"
                    />
                    <button
                        type="button"
                        tabindex="-1"
                        class="absolute inset-y-0 end-0 flex items-center justify-center px-3 text-muted-foreground transition-colors hover:text-foreground"
                        :aria-label="showPassword ? $t('trans.hide_password') : $t('trans.show_password')"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" />
                        <Eye v-else class="size-4" />
                    </button>
                </div>
                <PasswordStrengthIndicator v-if="showStrength" :password="modelValue ?? ''" />
            </div>
        </div>

        <InputError :message="error" :class="cn('flex w-full lowercase', !verticalLayout && 'justify-end')" />
    </div>
</template>

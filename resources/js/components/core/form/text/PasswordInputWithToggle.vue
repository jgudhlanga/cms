<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { Eye, EyeOff, Lock } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

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
    autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
    inputId: 'password',
    labelUppercase: false,
    isRequired: false,
    autocomplete: 'current-password',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input'): void;
}>();

const modelValue = useVModel(props, 'modelValue', emit);
const showPassword = ref(false);

const inputClasses = `
  min-h-11
  border border-input
  bg-background/80 text-foreground
  placeholder:text-muted-foreground
  rounded-xl
  pl-10 pr-11
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
                :type="showPassword ? 'text' : 'password'"
                :class="inputClasses"
                :placeholder="placeholder"
                :tabindex="tabindex"
                :autocomplete="autocomplete"
                @input="emit('input')"
            />
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center px-3">
                <Lock class="size-4 text-muted-foreground" />
            </span>
            <button
                type="button"
                tabindex="-1"
                class="absolute inset-y-0 end-0 flex min-h-11 min-w-11 items-center justify-center px-3 text-muted-foreground transition-colors hover:text-foreground"
                :aria-label="showPassword ? $t('trans.hide_password') : $t('trans.show_password')"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
            >
                <EyeOff v-if="showPassword" class="size-4" />
                <Eye v-else class="size-4" />
            </button>
        </div>

        <InputError :message="error" class="lowercase" />
    </div>
</template>

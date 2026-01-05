<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TextFieldType } from '@/enums/inputs';
import { cn } from '@/lib/utils';
import { onMounted } from 'vue';

interface Props {
    inputId: string;
    label?: string;
    type?: TextFieldType;
    classes?: string;
    error?: string | object;
    inputAutoFocus?: boolean;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: TextFieldType.text,
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
});

const baseClasses = `
  px-3 py-4
  border border-input
  bg-background text-foreground
  placeholder:text-muted-foreground
  rounded-md
  focus-visible:outline-none
  focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0
  disabled:cursor-not-allowed disabled:opacity-50
`;

const setAutoFocus = () => {
    const inputElement = document.getElementById(props.inputId) as HTMLInputElement;
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
                :class="cn('text-[16px] font-medium',error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')"
            >
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>

            <Input v-bind="$attrs" :id="inputId" :class="cn(baseClasses, classes)" :type="type" />
        </div>

        <InputError :message="error" :class="cn('flex w-full lowercase', !verticalLayout && 'justify-end')" />
    </div>
</template>

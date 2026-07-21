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
  p-3
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
    <div
        :class="
            cn(
                'w-full',
                verticalLayout ? 'flex flex-col' : 'grid grid-cols-[11rem_minmax(0,1fr)] items-center gap-x-3 gap-y-1',
            )
        "
    >
        <div
            :class="
                cn(
                    verticalLayout ? 'flex flex-col space-y-2' : 'contents',
                )
            "
        >
            <Label
                v-if="label"
                :for="inputId"
                :class="
                    cn(
                        'font-medium',
                        error && 'text-destructive',
                        labelUppercase && 'uppercase',
                        !verticalLayout && 'leading-snug',
                    )
                "
            >
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>

            <Input
                v-bind="$attrs"
                :id="inputId"
                :class="cn(baseClasses, classes, !verticalLayout && 'min-w-0')"
                :type="type"
            />
        </div>

        <InputError
            :message="error"
            :class="cn('flex w-full lowercase', !verticalLayout && (label ? 'col-start-2' : 'col-span-2'))"
        />
    </div>
</template>

<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field';
interface Props {
    inputId: string;
    defaultValue?: number | undefined;
    min?: number;
    max?: number;
    label?: string;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
}
withDefaults(defineProps<Props>(), {
    min: 0,
    defaultValue: 0,
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
});
</script>

<template>
    <NumberField :id="inputId" :default-value="defaultValue" :min="0" v-bind="$attrs">
        <Label v-if="label" :for="inputId">{{ label }}</Label>
        <NumberFieldContent>
            <NumberFieldDecrement />
            <NumberFieldInput />
            <NumberFieldIncrement />
        </NumberFieldContent>
    </NumberField>
</template>

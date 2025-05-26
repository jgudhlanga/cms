<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { RadioGroupOption } from '@/types/forms';
import { cn } from '@/lib/utils';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';

interface Props {
    options: RadioGroupOption[];
    defaultValue: string;
    label?: string;
    error?: string | object;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
}

defineProps<Props>();
</script>

<template>
    <RadioGroup :default-value="defaultValue" :orientation="'vertical'">
        <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase')" v-if="label"
            >{{ label }}
            <RequiredIndicator v-if="isRequired" />
        </Label>
        <div class="flex items-center space-x-2" v-for="option in options" :key="option.inputId">
            <RadioGroupItem :id="option.inputId" :value="option.value" class="p-3" />
            <Label :for="option.inputId">{{ option.label }}</Label>
        </div>
    </RadioGroup>
</template>

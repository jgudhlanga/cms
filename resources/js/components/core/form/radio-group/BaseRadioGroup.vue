<script setup lang="ts">
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { cn } from '@/lib/utils';
import { RadioGroupOption } from '@/types/forms';

interface Props {
    options: RadioGroupOption[];
    label?: string;
    error?: string | object;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
    orientation?: 'vertical' | 'horizontal';
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    orientation: 'vertical',
    verticalLayout: true,
});

const model = defineModel<string | boolean | null>();
</script>

<template>
    <RadioGroup
        v-model="model"
        :class="verticalLayout ? 'flex flex-col' : 'flex'"
        :orientation="orientation"
        :disabled="disabled"
    >
        <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase')" v-if="label"
            >{{ label }}
            <RequiredIndicator v-if="isRequired" />
        </Label>
        <div class="flex items-center space-x-2" v-for="option in options" :key="option.inputId">
            <RadioGroupItem :id="option.inputId" :value="option.value" class="p-3" />
            <template v-if="typeof option.label === 'string' && option.label.startsWith('<')">
                <!-- HTML string with icon -->
                <Label :for="option.inputId">
                    <span class="text-destructive" v-html="option.label" />
                </Label>
            </template>
            <template v-else-if="typeof option.label === 'string'">
                <!-- Plain text label -->
                <Label :for="option.inputId">{{ option.label }}</Label>
            </template>
            <template v-else>
                <!-- VNode / Component -->
                <Label :for="option.inputId">
                    <component :is="option.label" />
                </Label>
            </template>
        </div>
    </RadioGroup>
</template>

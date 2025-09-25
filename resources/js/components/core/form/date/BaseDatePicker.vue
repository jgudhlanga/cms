<script lang="ts" setup>
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

interface Props {
    inputId: string;
    label?: string;
    error?: string | object;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
}

withDefaults(defineProps<Props>(), {
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
});
</script>

<template>
    <div class="flex flex-col">
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-3')">
            <Label
                :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')"
                v-if="label"
                :for="inputId"
            >
                {{ label }}
                <RequiredIndicator v-if="isRequired" />
            </Label>
            <VueDatePicker
                :id="inputId"
                v-bind="$attrs"
                :always-clearable="false"
            />
        </div>
        <InputError :class="cn('flex w-full lowercase', !verticalLayout && 'justify-end')" :message="error" />
    </div>
</template>

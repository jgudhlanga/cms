<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import { SelectOption } from '@/types/utils';
import VueSelect from 'vue3-select-component';
import Empty from '../../util/Empty.vue';
import InputError from '../InputError.vue';
import { cn } from '@/lib/utils';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseDatePicker from '@/components/core/form/date/BaseDatePicker.vue';

interface Props {
    label?: string;
    placeholder?: string;
    options?: Array<SelectOption>| [];
    isClearable?: boolean;
    isMulti?: boolean;
    isSearchable?: boolean;
    loading?: boolean;
    error?: string | object;
    labelUppercase?: boolean,
    verticalLayout?: boolean,
    isRequired?: boolean,
}

withDefaults(defineProps<Props>(), {
    options: () => [],
    isClearable: true,
    isSearchable: true,
    labelUppercase:false,
    verticalLayout:true,
    isRequired: false
});

const model = defineModel<any>();
</script>
<template>
    <div class="flex flex-col">
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-3')">
            <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex items-center w-1/4')" v-if="label">
                {{ label }}<RequiredIndicator v-if="isRequired"/>
            </Label>
            <VueSelect
                :class="cn('custom-select', '')"
                :options="options"
                :placeholder="placeholder"
                v-model="model"
                v-bind="$attrs"
                :is-multi="isMulti"
                :is-searchable="isSearchable"
                :is-loading="loading"
                :is-clearable="isClearable"
            >
                <template #no-options>
                    <Empty :message="$t('trans.no_options_found')" />
                </template>
            </VueSelect>
        </div>
        <InputError class="lowercase" :message="error" />
    </div>
</template>
<style scoped>
.custom-select {
    --vs-outline-color: #30a8ff;
    --vs-spinner-color: var(--vs-outline-color);
    --vs-border-radius: 10px;
}

.error-select {
    --vs-outline-color: #dc2626;
}
</style>

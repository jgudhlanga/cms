<script lang="ts" setup>
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import { Label } from '@/components/ui/label';
import { resolveUiLabel } from '@/lib/uiLabel';
import { cn } from '@/lib/utils';
import { SelectOption } from '@/types/utils';
import VueSelect from 'vue3-select-component';
import Empty from '../../util/Empty.vue';
import InputError from '../InputError.vue';
import { computed } from 'vue';

interface Props {
    label?: string;
    placeholder?: string;
    options?: Array<SelectOption> | [];
    isClearable?: boolean;
    isMulti?: boolean;
    isSearchable?: boolean;
    loading?: boolean;
    error?: string | object;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
    isDisabled?: boolean;
    teleport?: string | false;
}

const props = withDefaults(defineProps<Props>(), {
    options: () => [],
    isClearable: true,
    isSearchable: true,
    isDisabled: false,
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
    teleport: undefined,
});

const model = defineModel<any>();

const resolvedTeleport = computed(() =>
    typeof props.teleport === 'string' && props.teleport.length > 0 ? props.teleport : undefined,
);
</script>
<template>
    <div class="flex flex-col">
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-2')">
            <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')" v-if="label">
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>
            <VueSelect
                v-bind="$attrs"
                :class="cn('custom-select', '')"
                :options="options"
                :placeholder="resolveUiLabel(placeholder || 'trans.select_one', $t)"
                :get-option-label="(option) => resolveUiLabel(String(option?.label ?? ''), $t)"
                v-model="model"
                :is-multi="isMulti"
                :is-searchable="isSearchable"
                :is-loading="loading"
                :is-clearable="isClearable"
                :is-disabled="isDisabled"
                :teleport="resolvedTeleport"
            >
                <template #no-options>
                    <Empty :message="resolveUiLabel('trans.no_options_found', $t)" />
                </template>
            </VueSelect>
        </div>
        <InputError class="lowercase" :message="error" />
    </div>
</template>
<style scoped>
.error-select {
    --vs-outline-color: hsl(var(--destructive));
    --vs-spinner-color: hsl(var(--destructive));
}
</style>

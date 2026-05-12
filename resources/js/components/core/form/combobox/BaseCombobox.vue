<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { SelectOption } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    label?: string;
    placeholder?: string;
    options?: Array<SelectOption>;
    error?: string | object;
    onSearch?: (search: string) => void;
    isLoading?: boolean;
    labelUppercase?: boolean;
    verticalLayout?: boolean;
    isRequired?: boolean;
    disabled?: boolean;
    widthClass?: string;
    /** When true, v-model is bound as SelectOption[] (empty array when nothing selected). */
    multiple?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    options: () => [],
    isLoading: false,
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
    widthClass: 'w-full',
    multiple: false,
});

type ComboboxModelValue = SelectOption | SelectOption[] | null | undefined;

const valueModel = defineModel<ComboboxModelValue>();

const isValidOption = (o: SelectOption | null | undefined): o is SelectOption =>
    !!o &&
    o.value != null &&
    o.value !== '' &&
    o.value !== '0' &&
    o.label != '---' &&
    o.label != '--';

const rootModel = computed<SelectOption | SelectOption[] | null | undefined>({
    get() {
        if (props.multiple) {
            const v = valueModel.value;
            if (Array.isArray(v)) {
                return v;
            }
            if (v && typeof v === 'object' && 'value' in v) {
                return [v];
            }
            return [];
        }
        const v = valueModel.value;
        if (Array.isArray(v)) {
            return v[0] ?? null;
        }
        return v ?? null;
    },
    set(v: SelectOption | SelectOption[] | null | undefined) {
        if (props.multiple) {
            valueModel.value = Array.isArray(v) ? v : [];
        } else {
            valueModel.value = Array.isArray(v) ? v[0] ?? null : v ?? null;
        }
    },
});

const fieldPlaceHolder = computed(() => {
    if (props.multiple) {
        const arr = Array.isArray(valueModel.value) ? valueModel.value : [];
        const valid = arr.filter(isValidOption);
        if (valid.length === 0) {
            return props.placeholder ?? trans('trans.select_one');
        }
        if (valid.length <= 2) {
            return valid.map((o) => o.label).join(', ');
        }
        return trans('students.filters_n_selected', { count: String(valid.length) });
    }

    const selected = Array.isArray(valueModel.value) ? valueModel.value[0] : valueModel.value;

    if (isValidOption(selected)) {
        return selected.label;
    }

    if (props.placeholder) {
        return props.placeholder;
    }

    return trans('trans.select_one');
});
</script>

<template>
    <div class="flex flex-col">
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-2')">
            <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')" v-if="label">
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>
            <Combobox v-model="rootModel" by="value" :multiple="props.multiple" :class="cn('', widthClass)" :disabled="disabled">
                <ComboboxAnchor as-child class="relative">
                    <ComboboxTrigger as-child>
                        <Button variant="outline" class="w-full justify-between text-left font-normal">
                            <span class="line-clamp-2 flex-1">{{ fieldPlaceHolder }}</span>
                            <SpinnerComponent v-if="isLoading" />
                            <ChevronsUpDown v-else class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                    </ComboboxTrigger>
                </ComboboxAnchor>
                <ComboboxList :class="cn('', widthClass)">
                    <div class="relative items-center">
                        <ComboboxInput
                            :placeholder="placeholder ?? $t('trans.select_one')"
                            :class="cn('h-10 rounded-none border-0 border-b pl-9 focus-visible:ring-0', '')"
                            @update:modelValue="onSearch ? onSearch($event) : null"
                        />
                        <span class="absolute inset-y-0 start-0 flex items-center justify-center px-3">
                            <Search class="text-muted-foreground size-4" />
                        </span>
                    </div>
                    <ComboboxEmpty v-if="!isLoading">
                        <Empty :message="$t('trans.no_options_found')" />
                    </ComboboxEmpty>
                    <ComboboxGroup>
                        <ComboboxItem v-for="option in options" :key="String(option.value)" :value="option">
                            {{ option.label }}
                            <ComboboxItemIndicator>
                                <Check :class="cn('ml-auto h-4 w-4')" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>
        </div>
        <InputError :class="cn('flex w-full lowercase', !verticalLayout && 'justify-end')" :message="error" />
    </div>
</template>

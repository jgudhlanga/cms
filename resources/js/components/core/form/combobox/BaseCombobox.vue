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
}

const props = withDefaults(defineProps<Props>(), {
    options: () => [],
    isLoading: false,
    labelUppercase: false,
    verticalLayout: true,
    isRequired: false,
    widthClass: 'w-full',
});
const valueModel = defineModel<SelectOption>();

const fieldPlaceHolder = computed(() => {
    const selected = valueModel.value;

    if (selected?.value != null && selected?.value !== '' && selected?.label != '---' && selected?.label != '--') {
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
        <div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-3')">
            <Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex w-1/4 items-center')" v-if="label">
                {{ label }}<RequiredIndicator v-if="isRequired" />
            </Label>
            <Combobox v-model="valueModel" by="label" :class="cn('', widthClass)" :disabled="disabled">
                <ComboboxAnchor as-child class="relative">
                    <ComboboxTrigger as-child>
                        <Button variant="outline" class="w-full justify-between">
                            {{ fieldPlaceHolder }}
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
                        <ComboboxItem v-for="option in options" :key="option.value" :value="option">
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

<script setup lang="ts">
import { cn } from '@/lib/utils';
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
	ComboboxTrigger
} from '@/components/ui/combobox';
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';
import { SelectOption } from '@/types/utils';
import Empty from '@/components/core/util/Empty.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import InputError from '@/components/core/form/InputError.vue';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';

interface Props {
	label?: string;
	placeholder?: string;
	options?: Array<SelectOption>;
	error?: string | object;
	onSearch?: (search: string) => void;
	isLoading?: boolean;
    labelUppercase?: boolean,
    verticalLayout?: boolean,
    isRequired?: boolean,
    disabled?: boolean,
    widthClass?: string,
}

const props = withDefaults(defineProps<Props>(), {
	options: () => [],
	isLoading: false,
    labelUppercase:false,
    verticalLayout:true,
    isRequired: false,
    widthClass: 'w-full'
});
const valueModel = defineModel<SelectOption>();

const fieldPlaceHolder = computed(() => {
	if (valueModel.value?.value) {
		return valueModel.value?.label;
	}
	if (props.placeholder !== undefined) {
		return props.placeholder;
	}
	return trans('trans.select_one');
});
</script>

<template>
	<div class="flex flex-col">
		<div :class="cn('flex space-x-3', verticalLayout && 'flex-col space-y-3')">
			<Label :class="cn(error && 'text-destructive', labelUppercase && 'uppercase', !verticalLayout && 'flex items-center w-1/4')" v-if="label">
                {{ label }}<RequiredIndicator v-if="isRequired"/>
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
							:class="cn('pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10',  '' )"
							@update:modelValue="onSearch ? onSearch($event) : null"
						/>
						<span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                            <Search class="size-4 text-muted-foreground" />
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

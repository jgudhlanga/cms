<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import { SelectOption } from '@/types/forms';
import VueSelect from 'vue3-select-component';
import Empty from '../../util/Empty.vue';
import InputError from '../InputError.vue';
import { cn } from '@/lib/utils';

interface Props {
	label?: string;
	placeholder?: string;
	options?: Array<SelectOption>;
	isClearable?: boolean;
	isMulti?: boolean;
	isSearchable?: boolean;
	loading?: boolean;
	error?: string | object;
}

withDefaults(defineProps<Props>(), {
		options: () => [],
		isClearable: true,
		isSearchable: true
	}
);

const model = defineModel<any>();
</script>
<template>
	<div class="flex flex-col">
		<div class="flex flex-col space-y-2">
			<Label :class="cn(error && 'text-destructive')" v-if="label">{{ label }}</Label>
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

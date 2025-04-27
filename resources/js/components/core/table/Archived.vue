<script setup lang="ts">
import { ref } from 'vue';
import { icons, IconName } from '@/lib/icons';


const props = withDefaults(defineProps<{
	trashed: number,
	handleArchived: Function,
	trashedCount?: any,
}>(), {
	trashed: 0
});

const options: Array<{ id: string, value: number, label: any }> = [
	{ id: 'without_archived', value: 0, label: 'trans.without_archived' },
	{ id: 'with_archived', value: 1, label: 'trans.with_archived' },
	{ id: 'only_archived', value: 2, label: 'trans.only_archived' }
];

const selectedValue = ref(props.trashed);

const handleClick = (value: any) => {
	props.handleArchived(value);
	selectedValue.value = +value;
};

</script>

<template>
	<div class="flex border-[1px] bg-accent py-2 px-4 rounded-full space-x-4 cursor-pointer">
		<button
			:class="`flex items-center space-x-2 hover:text-muted-foreground cursor-pointer ${selectedValue == option.value && 'text-primary'}`"
			v-for="option in options"
			@click="handleClick(option.value)">
			<component :is="icons[IconName.check_box]" v-if="selectedValue == option.value" size="16" />
			<div class="flex items-center font-extralight">
				{{ $t(option.label) }}
				<span v-if="option.value == 2" class=" text-destructive p-[1px] ml-1">{{ trashedCount }}</span>
			</div>
		</button>
	</div>
</template>

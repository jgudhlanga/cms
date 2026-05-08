<script setup lang="ts">
import { IconName, icons } from '@/lib/icons';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        trashed: number;
        handleArchived: Function;
        trashedCount?: any;
    }>(),
    {
        trashed: 0,
    },
);

const options: Array<{ id: string; value: number; label: any }> = [
    { id: 'without_archived', value: 0, label: 'trans.without_archived' },
    { id: 'with_archived', value: 1, label: 'trans.with_archived' },
    { id: 'only_archived', value: 2, label: 'trans.only_archived' },
];

const selectedValue = ref(props.trashed);

const handleClick = (value: any) => {
    props.handleArchived(value);
    selectedValue.value = +value;
};
</script>

<template>
    <div class="bg-accent flex cursor-pointer space-x-4 rounded-full border-[1px] px-4 py-2">
        <button
            :class="`hover:text-muted-foreground flex cursor-pointer items-center space-x-2 ${selectedValue == option.value && 'text-primary'}`"
            v-for="option in options"
            @click="handleClick(option.value)"
        >
            <component :is="icons[IconName.check_box]" v-if="selectedValue == option.value" size="16" />
            <div class="flex items-center font-extralight">
                {{ $t(option.label) }}
                <span v-if="option.value == 2" class="text-destructive ml-1 p-[1px]">{{ trashedCount }}</span>
            </div>
        </button>
    </div>
</template>

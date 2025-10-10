<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { PropType } from 'vue';

defineProps({
    subjects: {
        type: Array as PropType<any[]>,
        required: true,
    },
    type: {
        type: String as PropType<'main' | 'other'>,
        required: true,
    },
    getExamYear: {
        type: Function as PropType<(id: string) => string>,
        required: true,
    },
    getExamSitting: {
        type: Function as PropType<(id: string) => string>,
        required: true,
    },
    getGrade: {
        type: Function as PropType<(id: string) => string>,
        required: true,
    },
});
</script>

<template>
    <table class="hava-table my-4">
        <thead class="hava-thead">
            <tr>
                <th class="hava-th text-left">{{ $tChoice('trans.subject', 1) }}</th>
                <th class="hava-th text-center">{{ $tChoice('trans.year', 1) }}</th>
                <th class="hava-th text-center">{{ $tChoice('trans.sitting', 1) }}</th>
                <th class="hava-th text-center">{{ $tChoice('trans.grade', 1) }}</th>
                <th class="hava-th text-right">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="hava-tbody">
            <tr v-for="subject in subjects" :key="subject?.id ?? subject?.attributes?.subjectId ?? ''" class="hava-tr">
                <td class="hava-td">
                    {{ type === 'main' ? subject?.attributes?.name : subject?.attributes?.subject }}
                </td>
                <td class="hava-td text-center">
                    {{ getExamYear(String(type === 'main' ? subject?.id : subject.attributes?.subjectId)) }}
                </td>
                <td class="hava-td text-center">
                    {{ getExamSitting(String(type === 'main' ? subject?.id : subject.attributes?.subjectId)) }}
                </td>
                <td class="hava-td text-center">
                    {{ getGrade(String(type === 'main' ? subject?.id : subject.attributes?.subjectId)) }}
                </td>
                <td class="hava-td space-x-3 text-right">
                    <BaseButton :variant="ColorVariant.primary_outline" title="Edit" :size="ButtonSize.xs" classes="rounded-full">
                        <BaseIcon :name="IconName.edit" />
                    </BaseButton>
                    <BaseButton :variant="ColorVariant.danger_outline" title="Delete" :size="ButtonSize.xs" classes="rounded-full">
                        <BaseIcon :name="IconName.trash" />
                    </BaseButton>
                </td>
            </tr>
        </tbody>
    </table>
</template>

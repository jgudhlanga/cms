<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import GradeComboSelect from '@/components/core/form/combobox/GradeComboSelect.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { InertiaForm } from '@inertiajs/vue3';
import { CreateApplicationParams } from '@/types/portal';
import { storeToRefs } from 'pinia';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
    form: InertiaForm<CreateApplicationParams>;
}

defineProps<Props>();
const { o_level_subject_ids } = storeToRefs(useCreateApplicationFormStore());
</script>

<template>
    <HeadingSmall :title="$t('trans.o_level_results')" :description="$t('trans.o_level_results_description')" />
    <template v-if="levelRequirements?.relationships?.subjects && levelRequirements.relationships.subjects.length > 0">
        <table class="hava-table my-4">
            <thead class="hava-thead">
                <tr>
                    <th class="hava-th" align="left">{{ $tChoice('trans.subject', 1) }}</th>
                    <th class="hava-th w-[200px]" align="center">{{ $tChoice('trans.grade', 1) }}</th>
                </tr>
            </thead>
            <tbody class="hava-tbody">
                <tr class="hava-tr" v-for="subject in levelRequirements.relationships.subjects" :key="subject?.id ?? ''">
                    <td class="hava-td" align="left">{{ subject?.attributes?.name }}</td>
                    <td class="hava-td w-[200px]" align="center">
                        <GradeComboSelect :form="form" :is-required="true" :label="''" v-model="o_level_subject_ids" />
                    </td>
                </tr>
            </tbody>
        </table>
    </template>
    <template v-else>
        <Empty :message="$t('trans.no_subjects_found')" />
    </template>
</template>

<script lang="ts" setup>
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { Separator } from '@/components/ui/separator';
import { InstitutionDepartment } from '@/types/institution';
import { computed } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const attributes = computed(() => props.department.attributes);
const headOfDepartment = computed(() => attributes.value?.headOfDepartment?.trim() || null);
</script>
<template>
    <section class="flex flex-col space-y-8 pt-4">
        <div class="flex flex-col space-y-2">
            <HeadingSmall :title="`${$t('trans.ui_about_us')} — ${attributes?.department}`" />
            <template v-if="attributes?.description">
                <blockquote class="border-primary mt-2 flex w-full flex-col border-x-4 pl-6 italic">
                    {{ attributes.description }}
                </blockquote>
                <Separator class="mt-2" />
            </template>
            <div v-if="headOfDepartment" class="text-xs font-stretch-extra-condensed">
                &mdash;{{ headOfDepartment }} ({{ $t('trans.head_of_department') }})
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:w-2/3 sm:grid-cols-3">
            <LabelValue :label="$t('trans.division')" :value="attributes?.division ?? $t('trans.not_set')" />
            <LabelValue :label="$t('trans.ui_courses_offered')" :value="String(attributes?.coursesOfferedCount ?? 0)" />
            <LabelValue :label="$t('trans.ui_staff_count')" :value="String(attributes?.staffCount ?? 0)" />
        </div>

        <div v-if="attributes?.levelsOffered?.length">
            <HeadingSmall :title="$t('trans.ui_levels_offered')" />
            <ul class="mt-3 list-inside list-disc">
                <li v-for="level in attributes.levelsOffered" :key="level">{{ level }}</li>
            </ul>
        </div>
    </section>
</template>

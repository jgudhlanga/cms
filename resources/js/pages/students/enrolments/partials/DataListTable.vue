<script setup lang="ts">
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import TextLink from '@/components/core/util/TextLink.vue';
import { useUtils } from '@/composables/core/useUtils';
import { Enrolment } from '@/types/enrolments';

interface Props {
    enrolments: Enrolment[];
    title: string;
}

defineProps<Props>();

const { formatDate } = useUtils();
</script>

<template>
    <HeadingSmall :title="title" class="mb-5" />
    <div class="inline-block min-w-full overflow-auto align-middle">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.email') }}</th>
                    <th class="j-th text-left">{{ $t('trans.phone_number') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.tracking_number', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.department', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.level', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.course', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.mode_of_study', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <template v-if="enrolments.length > 0">
                    <tr class="j-tr" v-for="enrolment in enrolments" :key="enrolment.id">
                        <td class="j-td">
                            <TextLink :href="''" :title="enrolment?.attributes?.studentName" />
                        </td>
                        <td class="j-td">{{ enrolment?.attributes?.email }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.phoneNumber }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.applicationTrackingNumber }}</td>
                        <td class="j-td">{{ formatDate(enrolment?.attributes?.createdAt, 'L') }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.department }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.level }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.course }}</td>
                        <td class="j-td">{{ enrolment?.attributes?.modeOfStudy }}</td>
                    </tr>
                </template>
                <template v-else>
                    <tr class="j-tr">
                        <td class="j-td text-center" :colspan="9">
                            <Empty />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

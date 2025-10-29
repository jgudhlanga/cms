<script setup lang="ts">
import { EnrolmentApplication } from '@/types/enrolments';
import { hasAbility } from '@/lib/permissions';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
}

const props = defineProps<Props>();
const { applications } = props;
const { navigateTo } = useUtils();
</script>

<template>
    <div class="my-2">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $t('trans.tracking_number') }}</th>
                    <th class="j-th text-left">{{ $t('trans.application_date') }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.type', 1) }}</th>
                    <th class="j-th text-right">{{ $tChoice('trans.action', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <tr class="j-tr" v-for="(application, index) in applications" :key="application.applicationId">
                    <td class="j-td">{{ index + 1 }}</td>
                    <td class="j-td">
                        <TextLink
                            :title="application.studentName"
                            :href="route('enrolments.verify', {student_program: application.applicationId})"
                        />
                      </td>
                    <td class="j-td">{{ application.applicationTrackingNumber }}</td>
                    <td class="j-td">{{ application.applicationDate }}</td>
                    <td class="j-td">{{ application.phoneNumber }}</td>
                    <td class="j-td">{{ application.classListType }}</td>
                    <td class="j-td text-right">
                        <BaseButton
                            v-if="hasAbility('view:student-programs')"
                            title="Verify"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                            :variant="ColorVariant.primary_outline"
                            @click="navigateTo(route('enrolments.verify', {student_program: application.applicationId}))"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

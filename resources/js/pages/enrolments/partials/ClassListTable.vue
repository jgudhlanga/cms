<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { hasAbility } from '@/lib/permissions';
import { ClassListType, EnrolmentApplication } from '@/types/enrolments';

interface Props {
    departmentId: string;
    applications: EnrolmentApplication[];
    classListType: ClassListType;
}

const props = defineProps<Props>();
const { applications } = props;
const { navigateTo } = useUtils();

const getButtonTitle = (type: ClassListType) => {
    switch (type) {
        case 'provisional':
            return 'Verify';
        case 'waiting':
            return 'Verify';
        case 'verified':
            return 'Confirm';
        default:
            return 'View';
    }
};
const getRouteName = (type: ClassListType, applicationId: string) => {
    switch (type) {
        case 'provisional':
            return route('enrolments.verify', { student_application: applicationId, type: 'provisional' });
        case 'waiting':
            return route('enrolments.verify', { student_application: applicationId, type: 'waiting' });
        case 'verified':
            return route('enrolments.confirm', { student_application: applicationId, type: 'verified' });
        default:
            return '';
    }
};
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
                    <td class="j-td">{{ application.studentName }}</td>
                    <td class="j-td">{{ application.applicationTrackingNumber }}</td>
                    <td class="j-td">{{ application.applicationDate }}</td>
                    <td class="j-td">{{ application.phoneNumber }}</td>
                    <td class="j-td">{{ application.classListType }}</td>
                    <td class="j-td text-right">
                        <BaseButton
                            v-if="hasAbility('view:student-applications')"
                            :title="getButtonTitle(classListType as ClassListType)"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                            :variant="ColorVariant.primary_outline"
                            @click="navigateTo(getRouteName(classListType as ClassListType, String(application.applicationId)))"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

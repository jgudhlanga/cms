<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { IconName } from '@/enums/icons';
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
    classSizeIsCreated: boolean;
    selectedIds?: Set<number>;
    isSelected?: (applicationId: number) => boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => new Set(),
    isSelected: () => false,
});

const emit = defineEmits<{
    toggle: [applicationId: number, checked: boolean];
    selectGroup: [applications: EnrolmentApplication[], checked: boolean];
}>();

const { level, applications } = props;
const { isItTrue } = useUtils();
const { getClassListTypeClasses, addToClassList, getClassListTypeDescription, groupByClassListType, getClassListType } = useEnrolments();
const levelRequirements = computed(() => level?.relationships?.requirement);

const groupedApplications = computed(() => groupByClassListType(applications));
const selectableApplications = computed(() => applications.filter((app) => app.inClassList));
const allSelectableSelected = computed(
    () => selectableApplications.value.length > 0 && selectableApplications.value.every((app) => props.isSelected(app.applicationId)),
);

const onSelectAllOnList = (checked: boolean | 'indeterminate') => {
    emit('selectGroup', selectableApplications.value, checked === true);
};
</script>

<template>
    <div class="my-2">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th w-10 text-center">
                        <Checkbox
                            :model-value="allSelectableSelected"
                            :disabled="selectableApplications.length === 0"
                            :aria-label="$t('trans.ui_select_all_eligible')"
                            @update:model-value="onSelectAllOnList"
                        />
                    </th>
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.date', 1) }}</th>
                    <template v-if="Number(levelRequirements?.attributes?.requiredLevelId) > 0">
                        <th class="j-th text-center">{{ `${levelRequirements?.attributes?.requiredLevel} completed` }}</th>
                    </template>
                    <template v-if="isItTrue(levelRequirements?.attributes?.onlyReadWriteRequired)">
                        <th class="j-th text-center">{{ $t('trans.ui_read_write_acknowledged') }}</th>
                    </template>
                    <th class="j-th text-center">{{ $tChoice('trans.status', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <template v-for="(groupApplications, classListType) in groupedApplications" :key="classListType">
                    <tr class="j-tr">
                        <td class="" colspan="100%">
                            <div
                                :class="`flex w-full items-center space-x-2 border-b-1 px-3 py-2 text-sm uppercase ${getClassListTypeClasses(classListType)}`"
                            >
                                <span>{{ classListType }}</span>
                                <span class="text-xs lowercase">{{ `(${getClassListTypeDescription(classListType)})` }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr class="j-tr" v-for="(application, index) in groupApplications" :key="application.applicationId">
                        <td class="j-td text-center">
                            <Checkbox
                                v-if="application.inClassList"
                                :model-value="isSelected(application.applicationId)"
                                :aria-label="application.studentName"
                                @update:model-value="(checked) => emit('toggle', application.applicationId, checked === true)"
                            />
                        </td>
                        <td class="j-td">{{ index + 1 }}</td>
                        <td class="j-td">{{ application.studentName }}</td>
                        <td class="j-td">{{ application.phoneNumber }}</td>
                        <td class="j-td text-center">{{ application.applicationDate }}</td>
                        <template v-if="Number(levelRequirements?.attributes?.requiredLevelId) > 0">
                            <th class="j-th text-center">
                                <BaseIcon
                                    :name="isItTrue(application?.requiredLevelCompleted) ? IconName.check_done : IconName.close"
                                    :class="`h-4 w-full ${isItTrue(application?.requiredLevelCompleted) ? 'text-green-600' : 'text-red-600'}`"
                                />
                            </th>
                        </template>
                        <template v-if="isItTrue(levelRequirements?.attributes?.onlyReadWriteRequired)">
                            <td class="j-th text-center">
                                <BaseIcon
                                    :name="isItTrue(application?.readWriteAcknowledged) ? IconName.check_done : IconName.close"
                                    :class="`h-4 w-full ${isItTrue(application?.readWriteAcknowledged) ? 'text-green-600' : 'text-red-600'}`"
                                />
                            </td>
                        </template>
                        <td class="j-td text-center">
                            <template v-if="application.inClassList">
                                <span>{{ application.classListType }}</span>
                            </template>
                            <IconButton
                                v-else
                                :icon="IconName.add"
                                @click="addToClassList(String(application.applicationId), getClassListType(index, classSize))"
                            />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

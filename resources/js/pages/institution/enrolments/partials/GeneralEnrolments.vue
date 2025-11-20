<script setup lang="ts">
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
}

const props = defineProps<Props>();
const { level, applications } = props;
const { isItTrue } = useUtils();
const { addToClassList, getRowClassList, getClassListIconClass, getClassListType, showAddToClassListBtn } = useEnrolments();
const levelRequirements = computed(() => level?.relationships?.requirement);
</script>

<template>
    <div class="my-2">
        <table class="j-table">
            <thead class="j-thead">
                <tr class="j-th">
                    <th class="j-th text-left">#</th>
                    <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                    <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                    <th class="j-th text-center">{{ $tChoice('trans.date', 1) }}</th>
                    <template v-if="Number(levelRequirements?.attributes?.requiredLevelId) > 0">
                        <th class="j-th text-center">{{ `${levelRequirements?.attributes?.requiredLevel} completed` }}</th>
                    </template>
                    <template v-if="isItTrue(levelRequirements?.attributes?.onlyReadWriteRequired)">
                        <th class="j-th text-center">Read / Write Acknowledged</th>
                    </template>
                    <th class="j-th text-center">{{ $tChoice('trans.status', 1) }}</th>
                </tr>
            </thead>
            <tbody class="j-tbody">
                <tr :class="getRowClassList(index, classSize)" v-for="(application, index) in applications" :key="application.applicationId">
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
                            <BaseIcon
                                v-if="index + 1 <= slotSize * 2"
                                :name="IconName.check_done"
                                :class="`h-4 w-full ${getClassListIconClass(index, classSize)}`"
                            />
                        </template>
                        <template v-else>
                            <IconButton
                                v-if="showAddToClassListBtn(index, classSize)"
                                :icon="IconName.add"
                                @click="addToClassList(String(application.applicationId), getClassListType(index, classSize))"
                            />
                            <BaseIcon v-else :name="IconName.close" class="h-4 w-full" />
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

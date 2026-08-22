<script setup lang="ts">
import { useUtils } from '@/composables/core/useUtils';
import { useEnrolments } from '@/composables/students/useEnrolments';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { enrolmentStatusListPermission } from '@/lib/enrolmentStatusNavigation';
import { enrolmentStatusFromQuery } from '@/lib/enrolmentStatusOrigin';
import {
    gradeBadgeClass,
    shortSubjectLabel,
    sittingBadgeClass,
    toTitleCase,
    yearSuffix,
} from '@/lib/enrolmentClassListPresentation';
import { hasAbility } from '@/lib/permissions';
import { DepartmentLevel } from '@/types/department-meta-data';
import { ClassListType, EnrolmentApplication } from '@/types/enrolments';
import { Lock } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    departmentId: string;
    applications: EnrolmentApplication[];
    classListType?: ClassListType | string;
    level: DepartmentLevel;
    isOLevel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isOLevel: false,
});

const { navigateTo, getQueryParams, formatDate, isItTrue } = useUtils();
const { getMainSubjectGrade, getOtherSubjectGrades } = useEnrolments();
const originQuery = enrolmentStatusFromQuery(getQueryParams());

const levelRequirements = computed(() => props.level?.relationships?.requirement);
const requirementSubjects = computed(() => levelRequirements.value?.relationships?.subjects ?? []);
const requiresPriorLevel = computed(() => Number(levelRequirements.value?.attributes?.requiredLevelId) > 0);
const requiresReadWrite = computed(() => isItTrue(levelRequirements.value?.attributes?.onlyReadWriteRequired));

const resolveType = (application: EnrolmentApplication): ClassListType | string => {
    return (props.classListType || application.classListType || '') as ClassListType | string;
};

const getButtonTitle = (type: ClassListType | string) => {
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

const canOpenApplication = (type: ClassListType | string) => {
    const permission = enrolmentStatusListPermission(String(type));

    return permission !== null && hasAbility(permission);
};

const isFinalLocked = (application: EnrolmentApplication): boolean =>
    resolveType(application) === 'final' || application.classListType === 'final';

const getRouteName = (type: ClassListType | string, applicationId: string) => {
    switch (type) {
        case 'provisional':
            return route('enrolments.verify', { student_application: applicationId, type: 'provisional', ...originQuery });
        case 'waiting':
            return route('enrolments.verify', { student_application: applicationId, type: 'waiting', ...originQuery });
        case 'verified':
            return route('enrolments.confirm', { student_application: applicationId, type: 'verified', ...originQuery });
        case 'final':
            return route('enrolments.confirm', { student_application: applicationId, type: 'verified', ...originQuery });
        default:
            return route('enrolments.verify', { student_application: applicationId, type: 'provisional', ...originQuery });
    }
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-160 text-left text-xs">
            <thead>
                <tr class="border-b border-border/60 bg-muted/40 text-[10px] tracking-wide text-muted-foreground uppercase">
                    <th class="w-8 px-1 py-2">#</th>
                    <th class="px-2 py-2">Applicant</th>
                    <th class="px-2 py-2">Applied</th>
                    <template v-if="isOLevel">
                        <th class="px-1 py-2 text-center">Sit.</th>
                        <th class="px-1 py-2 text-center">First</th>
                        <th
                            v-for="subject in requirementSubjects"
                            :key="`h_${subject.id}`"
                            class="px-1 py-2 text-center"
                        >
                            {{ shortSubjectLabel(subject.attributes?.name ?? '') }}
                        </th>
                        <th
                            v-for="n in levelRequirements?.attributes?.otherSubjectsCount"
                            :key="`ho_${n}`"
                            class="px-1 py-2 text-center"
                        >
                            Opt {{ n }}
                        </th>
                        <th class="px-2 py-2 text-right">Score</th>
                    </template>
                    <template v-else>
                        <th v-if="requiresPriorLevel" class="px-2 py-2 text-center">
                            {{ levelRequirements?.attributes?.requiredLevel }} done
                        </th>
                        <th v-if="requiresReadWrite" class="px-2 py-2 text-center">Read/Write</th>
                    </template>
                    <th class="w-16 px-2 py-2 text-center">{{ $tChoice('trans.action', 1) }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(application, index) in applications"
                    :key="application.applicationId"
                    class="border-b border-border/40 last:border-0 hover:bg-muted/30"
                >
                    <td class="px-1 py-1.5 tabular-nums text-muted-foreground">{{ index + 1 }}</td>
                    <td class="px-2 py-1.5">
                        <div class="font-semibold text-foreground">{{ toTitleCase(application.studentName) }}</div>
                        <div class="text-[10px] text-muted-foreground">
                            {{ application.applicationTrackingNumber || '—' }}
                            <span v-if="application.phoneNumber"> · {{ application.phoneNumber }}</span>
                        </div>
                    </td>
                    <td class="px-2 py-1.5 whitespace-nowrap text-muted-foreground">
                        {{ formatDate(application.applicationDate, 'DD MMM YY') }}
                    </td>
                    <template v-if="isOLevel">
                        <td class="px-1 py-1.5 text-center">
                            <span
                                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold"
                                :class="sittingBadgeClass(Number(application.examSittingsCount) || 1)"
                            >
                                {{ application.examSittingsCount || 1 }}
                            </span>
                        </td>
                        <td class="px-1 py-1.5 text-center tabular-nums">{{ application.firstExamYear || '—' }}</td>
                        <td
                            v-for="subject in requirementSubjects"
                            :key="`g_${application.applicationId}_${subject.id}`"
                            class="px-1 py-1.5 text-center"
                        >
                            <span
                                v-if="getMainSubjectGrade(application.academicResults ?? [], String(subject.id))"
                                class="inline-flex items-baseline rounded px-1.5 py-0.5 text-[10px] font-bold"
                                :class="
                                    gradeBadgeClass(
                                        getMainSubjectGrade(application.academicResults ?? [], String(subject.id))?.grade,
                                    )
                                "
                            >
                                {{ getMainSubjectGrade(application.academicResults ?? [], String(subject.id))?.grade }}
                                <sup class="ml-0.5 text-[8px] font-semibold opacity-80">
                                    {{
                                        yearSuffix(
                                            getMainSubjectGrade(application.academicResults ?? [], String(subject.id))?.examYear,
                                        )
                                    }}
                                </sup>
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            v-for="(result, oi) in getOtherSubjectGrades(application.academicResults ?? [], level)"
                            :key="`o_${application.applicationId}_${oi}`"
                            class="px-1 py-1.5 text-center"
                        >
                            <span
                                class="inline-flex items-baseline rounded px-1.5 py-0.5 text-[10px] font-bold"
                                :class="gradeBadgeClass(result.grade)"
                            >
                                {{ result.grade }}
                                <sup class="ml-0.5 text-[8px] font-semibold opacity-80">{{ yearSuffix(result.examYear) }}</sup>
                            </span>
                        </td>
                        <td class="px-2 py-1.5 text-right">
                            <span class="inline-flex rounded-md bg-muted px-1.5 py-0.5 text-[10px] font-bold tabular-nums">
                                {{ application.totalScore }} pts
                            </span>
                        </td>
                    </template>
                    <template v-else>
                        <td v-if="requiresPriorLevel" class="px-2 py-1.5 text-center">
                            <span
                                class="text-[10px] font-semibold"
                                :class="isItTrue(application.requiredLevelCompleted) ? 'text-emerald-600' : 'text-red-600'"
                            >
                                {{ isItTrue(application.requiredLevelCompleted) ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td v-if="requiresReadWrite" class="px-2 py-1.5 text-center">
                            <span
                                class="text-[10px] font-semibold"
                                :class="isItTrue(application.readWriteAcknowledged) ? 'text-emerald-600' : 'text-red-600'"
                            >
                                {{ isItTrue(application.readWriteAcknowledged) ? 'Yes' : 'No' }}
                            </span>
                        </td>
                    </template>
                    <td class="px-2 py-1.5 text-center">
                        <span
                            v-if="isFinalLocked(application)"
                            class="inline-flex items-center justify-center text-emerald-700"
                            title="Final class list entries are locked"
                        >
                            <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                            <span class="sr-only">Locked</span>
                        </span>
                        <BaseButton
                            v-else-if="canOpenApplication(resolveType(application))"
                            :title="getButtonTitle(resolveType(application))"
                            :size="ButtonSize.xs"
                            classes="rounded-full"
                            :variant="ColorVariant.primary_outline"
                            @click="navigateTo(getRouteName(resolveType(application), String(application.applicationId)))"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

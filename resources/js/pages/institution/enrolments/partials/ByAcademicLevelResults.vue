<script setup lang="ts">
import { DepartmentLevel } from '@/types/department-meta-data';
import { EnrolmentApplication, OLeveResult } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    level: DepartmentLevel;
    departmentId: string;
    applications: EnrolmentApplication[];
    classSize: number;
    slotSize: number;
}

const props = defineProps<Props>();

const { level } = props;

const levelRequirements = computed(() => level?.relationships?.requirement);
const requirementSubjects = computed(() => level?.relationships?.requirement?.relationships?.subjects);
/**
 * Helper: get grade for a required subject
 */
const getMainSubjectGrade = (results: OLeveResult[], subjectId: string | number): OLeveResult | null | undefined => {
    return results.find((r) => String(r.subjectId) === String(subjectId));
};

/**
 * Helper: get grades for other subjects beyond required ones
 */
const getOtherSubjectGrades = (results: OLeveResult[]): Record<number, OLeveResult> => {
    const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
    const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
    const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;

    const requiredIds = requiredSubjects.map((s: any) => String(s.id));
    const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));

    // 🧮 Grade priority (lower = better)
    const gradeOrder: Record<string, number> = { A: 1, B: 2, C: 3 };

    // 🧩 Sort by grade quality: A → B → C → anything else → empty
    const sortedOthers = otherSubjects.sort((a, b) => {
        const aVal = gradeOrder[a.grade?.trim()] || 999;
        const bVal = gradeOrder[b.grade?.trim()] || 999;
        return aVal - bVal;
    });

    // 🧾 Pick top N grades, fill remaining with "N/A"
    const grades: Record<number, OLeveResult> = {};
    for (let i = 0; i < otherSubjectsCount; i++) {
        grades[i + 1] = sortedOthers[i] || ({ grade: '---', subject: '---' } as OLeveResult);
    }

    return grades;
};

const faultyApplications = computed(() => {
    return props.applications.filter((app) => {
        const results: OLeveResult[] = app.academicResults || [];
        const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

        const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
        const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
        const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;

        const requiredIds = requiredSubjects.map((s: any) => String(s.id));

        const getGradeScore = (grade: string, examYear: string, firstExamYear: string, uniqueYears: string[]) => {
            const trimmed = grade?.trim() || 'N/A';
            if (trimmed === 'N/A' || trimmed === '---') return 9;

            let sittingIndex = 0;
            const uniqueIndex = uniqueYears.findIndex((y) => y === examYear);
            sittingIndex = uniqueIndex >= 0 ? uniqueIndex : 0;

            switch (trimmed) {
                case 'A':
                    return 1 + sittingIndex;
                case 'B':
                    return 2 + sittingIndex;
                case 'C':
                    return 3 + sittingIndex;
                default:
                    return 9;
            }
        };

        const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => a - b);
        const firstExamYear = uniqueYears[0] ?? 0;

        const mainScores = requiredIds.map((sid) => {
            const r = results.find((res) => String(res.subjectId) === sid);
            if (!r) return 9;
            return getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears);
        });

        const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));
        const sortedOthers = otherSubjects
            .sort(
                (a, b) =>
                    getGradeScore(a.grade || 'N/A', a.examYear, firstExamYear, uniqueYears) -
                    getGradeScore(b.grade || 'N/A', b.examYear, firstExamYear, uniqueYears),
            )
            .slice(0, otherSubjectsCount);

        while (sortedOthers.length < otherSubjectsCount) {
            sortedOthers.push({ grade: 'N/A', examYear: firstExamYear } as OLeveResult);
        }

        const otherScores = sortedOthers.map((r) => getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears));

        const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);

        return hasInvalidGrade || hasNoPayment;
    });
});

/**
 * 🧮 Compute each applicant's score and sort list
 */
const sortedApplications = computed(() => {
    const requiredSubjects = level?.relationships?.requirement?.relationships?.subjects || [];
    const otherSubjectsCountRaw = level?.relationships?.requirement?.attributes?.otherSubjectsCount ?? 0;
    const otherSubjectsCount = Number(otherSubjectsCountRaw) || 0;

    const requiredIds = requiredSubjects.map((s: any) => String(s.id));

    const getGradeScore = (grade: string, examYear: string, firstExamYear: string, uniqueYears: string[]) => {
        const trimmed = grade?.trim() || 'N/A';
        if (trimmed === 'N/A' || trimmed === '---') return 9;

        let sittingIndex = 0;
        if (examYear === firstExamYear) sittingIndex = 0;
        else sittingIndex = uniqueYears.findIndex((y) => y === examYear);

        switch (trimmed) {
            case 'A':
                return 1 + sittingIndex;
            case 'B':
                return 2 + sittingIndex;
            case 'C':
                return 3 + sittingIndex;
            default:
                return 9;
        }
    };

    // Reset faulty applications

    const scored: typeof props.applications = [];

    props.applications.forEach((app) => {
        const results: OLeveResult[] = app.academicResults || [];
        const hasNoPayment = !app.receiptAmount || app.receiptAmount <= 0;

        // Unique exam years ascending
        const uniqueYears = Array.from(new Set(results.map((r) => r.examYear))).sort((a, b) => a - b);
        const firstExamYear = uniqueYears[0] ?? 0;

        // Required subjects
        const mainScores = requiredIds.map((sid) => {
            const r = results.find((res) => String(res.subjectId) === sid);
            if (!r) return 9;
            return getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears);
        });

        // Other subjects
        const otherSubjects = results.filter((r) => !requiredIds.includes(String(r.subjectId)));
        const sortedOthers = otherSubjects
            .sort(
                (a, b) =>
                    getGradeScore(a.grade || 'N/A', a.examYear, firstExamYear, uniqueYears) -
                    getGradeScore(b.grade || 'N/A', b.examYear, firstExamYear, uniqueYears),
            )
            .slice(0, otherSubjectsCount);

        while (sortedOthers.length < otherSubjectsCount) {
            sortedOthers.push({ grade: 'N/A', examYear: firstExamYear } as OLeveResult);
        }

        const otherScores = sortedOthers.map((r) => getGradeScore(r.grade || 'N/A', r.examYear, firstExamYear, uniqueYears));

        const totalScore = [...mainScores, ...otherScores].reduce((sum, s) => sum + s, 0);

        // Check if application is faulty
        const hasInvalidGrade = [...mainScores, ...otherScores].some((score) => score >= 9);
        if (hasInvalidGrade || hasNoPayment) {
            return; // skip adding to sortedApplications
        }

        scored.push({
            ...app,
            totalScore,
            hasNoPayment,
            hasInvalidGrade,
        });
    });

    // Sort valid applications by totalScore ascending
    return scored.sort((a, b) => a.totalScore - b.totalScore);
});
</script>

<template>
    <table class="j-table">
        <thead class="j-thead">
            <tr class="j-th">
                <th class="j-th text-left">#</th>
                <th class="j-th text-left">{{ $tChoice('trans.name', 1) }}</th>
                <th class="j-th text-left">{{ $tChoice('trans.phone', 1) }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.amount', 1) }}</th>
                <th class="j-th text-center">Sitting Count</th>
                <th class="j-th text-center">First Sitting</th>
                <th class="j-th text-center" v-for="subject in requirementSubjects" :key="`tr_${subject.id}`">{{ subject?.attributes?.name }}</th>
                <th class="j-th text-center" v-for="sub in levelRequirements?.attributes.otherSubjectsCount" :key="`${sub}th_other_sub`">
                    {{ `${$t('trans.other')} ${Number(sub)}` }}
                </th>
                <th class="j-th text-center">{{ $tChoice('trans.score', 1) }}</th>
                <th class="j-th text-center">{{ $tChoice('trans.action', 2) }}</th>
            </tr>
        </thead>
        <tbody class="j-tbody">
            <tr :class="`${index + 1 <= slotSize ? 'bg-green-100' : 'j-tr'}`" v-for="(application, index) in sortedApplications" :key="application.applicationId">
                <td class="j-td">{{ index + 1 }}</td>
                <td class="j-td">{{ application.studentName }}</td>
                <td class="j-td">{{ application.phoneNumber }}</td>
                <td class="j-td text-center">{{ application.receiptAmount }}</td>
                <td class="j-td text-center">{{ application.examSittingsCount }}</td>
                <td class="j-td text-center">{{ application.firstExamYear }}</td>
                <td class="j-td text-center" v-for="subject in requirementSubjects" :key="`td_${subject.id}`">
                    {{ getMainSubjectGrade(application?.academicResults, String(subject?.id))?.grade }}
                    <span class="text-[8px]">{{ getMainSubjectGrade(application?.academicResults, String(subject?.id))?.examYear ?? '---' }}</span>
                </td>
                <td
                    class="j-td text-center"
                    v-for="result in getOtherSubjectGrades(application?.academicResults)"
                    :key="`${result.gradeId}_other_sub`"
                >
                    {{ result.grade }}
                    <span class="text-[8px]">{{ result.examYear }}</span>
                </td>
                <td class="j-td text-center">{{ application.totalScore }}</td>
                <td class="j-td"></td>
            </tr>
            <template v-if="faultyApplications.length > 0">
                <tr class="bg-red-100" v-for="application in faultyApplications" :key="application.applicationId">
                    <td class="j-td">{{ application.studentId }}</td>
                    <td class="j-td">{{ application.studentName }}</td>
                    <td class="j-td">{{ application.phoneNumber }}</td>
                    <td class="j-td text-center">{{ application.receiptAmount }}</td>
                    <td class="j-td text-center">{{ application.examSittingsCount }}</td>
                    <td class="j-td text-center">{{ application.firstExamYear }}</td>
                    <td class="j-td text-center" v-for="subject in requirementSubjects" :key="`td_${subject.id}`">
                        {{ getMainSubjectGrade(application?.academicResults, String(subject?.id))?.grade }}
                        <span class="text-[8px]"
                            >({{ getMainSubjectGrade(application?.academicResults, String(subject?.id))?.examYear ?? '---' }})</span
                        >
                    </td>
                    <td
                        class="j-td text-center"
                        v-for="result in getOtherSubjectGrades(application?.academicResults)"
                        :key="`${result.gradeId}_other_sub`"
                    >
                        {{ result.grade }}
                        <span class="text-[8px]">({{ result.examYear }})</span>
                    </td>
                    <td class="j-td text-center">{{ application.totalScore ?? '---' }}</td>
                    <td class="j-td">
                        <span class="text-red-800">faulty</span>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</template>

import type { StudentProgrammeModule, StudentProgrammeSemester } from '@/types/students';
import { trans } from 'laravel-vue-i18n';

export const programmeHeading = (
    level: string | null,
    course: string | null,
    courseCode: string | null,
): string => {
    let title = '';

    if (level && course) {
        title = `${level} ${trans('students.programme_title_in')} ${course}`;
    } else if (level) {
        title = level;
    } else if (course) {
        title = course;
    }

    if (!courseCode) {
        return title;
    }

    return title ? `${title} (${courseCode})` : `(${courseCode})`;
};

export const semesterDurationHours = (semester: StudentProgrammeSemester): number =>
    semester.module.reduce((total, module) => total + (Number(module.durationInHours) || 0), 0);

export const formatDurationHours = (hours: number | null | undefined): string => {
    const count = Number(hours) || 0;

    if (count <= 0) {
        return trans('students.not_available');
    }

    return trans('students.hours_short', { count: String(count) });
};

export const displayValue = (value: string | number | null | undefined): string => {
    if (value === null || value === undefined || value === '') {
        return trans('students.not_available');
    }

    return String(value);
};

export const isMissingValue = (value: string | number | null | undefined): boolean =>
    value === null || value === undefined || value === '';

export const missingValueTextClass = (value: string | number | null | undefined): string =>
    isMissingValue(value) ? 'text-amber-500' : '';

export const isMissingDisplay = (display: string): boolean =>
    display === trans('students.not_available') || display === trans('students.pending');

export const missingDisplayTextClass = (display: string): string =>
    isMissingDisplay(display) ? 'text-amber-500' : '';

export interface SemesterHeaderMeta {
    label: string;
    labelMissing: boolean;
    year: string;
    yearMissing: boolean;
    duration: string;
    durationMissing: boolean;
    moduleCount: number;
}

export const semesterHeaderMeta = (semester: StudentProgrammeSemester): SemesterHeaderMeta => {
    const durationHours = semesterDurationHours(semester);
    const duration = formatDurationHours(durationHours);

    return {
        label: displayValue(semester.label),
        labelMissing: isMissingValue(semester.label),
        year: displayValue(semester.year),
        yearMissing: isMissingValue(semester.year),
        duration,
        durationMissing: durationHours <= 0,
        moduleCount: semester.module.length,
    };
};

export const statusBadgeClass = (status: string | null | undefined): string => {
    const normalized = (status ?? '').toLowerCase();

    if (normalized.includes('complete')) {
        return 'text-emerald-400';
    }

    if (normalized.includes('active') || normalized.includes('progress')) {
        return 'text-primary';
    }

    return 'text-muted-foreground';
};

export const gradeBadgeClass = (grade: string | null | undefined): string => {
    const g = grade ?? '';

    if (!g) {
        return 'text-amber-500';
    }

    if (g === 'IP') {
        return 'text-amber-500';
    }

    if (g.startsWith('A')) {
        return 'text-emerald-400';
    }

    if (g.startsWith('B')) {
        return 'text-primary';
    }

    if (g.startsWith('C')) {
        return 'text-amber-400';
    }

    return 'text-red-400';
};

export const scoreBarColor = (score: number): string => {
    if (score >= 75) {
        return 'bg-emerald-400';
    }

    if (score >= 60) {
        return 'bg-blue-400';
    }

    if (score >= 50) {
        return 'bg-amber-400';
    }

    return 'bg-red-400';
};

export const scoreLabel = (score: number): string => {
    if (score >= 75) {
        return trans('students.score_distinction');
    }

    if (score >= 60) {
        return trans('students.score_merit');
    }

    if (score >= 50) {
        return trans('students.score_pass');
    }

    return trans('students.score_below_pass');
};

export const moduleGradeDisplay = (module: StudentProgrammeModule): string => {
    const total = module.courseWork?.aggregation.courseWorkTotal60;

    if (total !== null && total !== undefined) {
        return String(Math.round(total));
    }

    const hasPartialMarks = module.courseWork?.assessments?.some((assessment) => assessment.mark !== null);

    if (hasPartialMarks) {
        return trans('students.course_work_in_progress');
    }

    return module.grade ? module.grade : trans('students.not_available');
};

export const moduleGradeBadgeClass = (module: StudentProgrammeModule): string => {
    const total = module.courseWork?.aggregation.courseWorkTotal60;

    if (total !== null && total !== undefined) {
        const percent = (total / 60) * 100;

        return gradeBadgeClass(percent >= 50 ? 'B' : percent >= 40 ? 'C' : 'F');
    }

    const hasPartialMarks = module.courseWork?.assessments?.some((assessment) => assessment.mark !== null);

    if (hasPartialMarks) {
        return 'text-amber-500';
    }

    return gradeBadgeClass(module.grade);
};

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

export const semesterTitle = (label: string | null, year: string | null): string =>
    [label, year].filter(Boolean).join(` ${trans('students.semester_title_separator')} `);

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

export const statusBadgeClass = (status: string | null | undefined): string => {
    const normalized = (status ?? '').toLowerCase();

    if (normalized.includes('complete')) {
        return 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30';
    }

    if (normalized.includes('active') || normalized.includes('progress')) {
        return 'bg-primary/15 text-primary ring-1 ring-primary/30';
    }

    return 'bg-muted text-muted-foreground ring-1 ring-border';
};

export const gradeBadgeClass = (grade: string | null | undefined): string => {
    const g = grade ?? '';

    if (!g) {
        return 'bg-muted text-muted-foreground';
    }

    if (g === 'IP') {
        return 'bg-muted text-muted-foreground';
    }

    if (g.startsWith('A')) {
        return 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30';
    }

    if (g.startsWith('B')) {
        return 'bg-primary/15 text-primary ring-1 ring-primary/30';
    }

    if (g.startsWith('C')) {
        return 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30';
    }

    return 'bg-red-500/15 text-red-400 ring-1 ring-red-500/30';
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

export const moduleGradeDisplay = (module: StudentProgrammeModule): string =>
    module.grade ? module.grade : trans('students.not_available');

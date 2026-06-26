import type { Student, StudentHeader } from '@/types/students';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';

export function useStudentProfileHeader(student: MaybeRefOrGetter<Student | null | undefined>) {
    const headerData = computed<StudentHeader>(() => {
        const value = toValue(student);

        return {
            studentId: value?.id ?? '',
            studentName: value?.relationships?.user?.attributes.name ?? '',
            avatarUrl: value?.relationships?.user?.attributes.avatarUrl ?? '',
            studentNumber: value?.attributes.studentNumber ?? '',
            level: value?.attributes.level ?? '',
            course: value?.attributes.course ?? '',
            academicCalendar: value?.relationships?.latestEnrolment?.attributes.academicCalendar ?? '',
            academicYearOption: value?.relationships?.latestEnrolment?.attributes.academicYearOption ?? '',
            enrolmentStatus: value?.attributes.enrolmentStatus ?? '',
            applicationStatus: value?.attributes.applicationStatus ?? '',
            intakePeriod: value?.attributes.intakePeriod ?? '',
            applicationTrackingNumber: value?.attributes.applicationTrackingNumber ?? '',
            profileContext: value?.attributes.profileContext ?? null,
            modeOfStudy: value?.attributes.modeOfStudy ?? '',
            department: value?.attributes.department ?? '',
        };
    });

    return { headerData };
}

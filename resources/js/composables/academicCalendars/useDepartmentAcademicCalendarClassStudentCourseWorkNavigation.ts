import type { AcademicCalendar, AcademicCalendarClassPreviewStudent, ClassConfig } from '@/types/academic-calendar';
import type { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import type { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import type { Ref } from 'vue';
import { computed } from 'vue';

export function useDepartmentAcademicCalendarClassStudentCourseWorkNavigation(
    department: Ref<InstitutionDepartment>,
    academicCalendar: Ref<AcademicCalendar>,
    course: Ref<DepartmentCourse>,
    level: Ref<DepartmentLevel>,
    mode: Ref<ModeOfStudy>,
    classConfig: Ref<ClassConfig | null>,
    academicCalendarClass: Ref<{ id: number; name: string }>,
    student: Ref<AcademicCalendarClassPreviewStudent>,
) {
    const classShowUrl = computed(() =>
        route('academic-calendars.department-classes.show', {
            institution_department: String(department.value.id),
            calendar_year: String(academicCalendar.value.attributes.calendarYear),
            academic_calendar_class: String(academicCalendarClass.value.id),
        }),
    );

    const breadcrumbs = computed<Array<Link>>(() => {
        const departmentShowUrl = route('institution-departments.show', String(department.value.id));

        return [
            { transChoiceKey: 'institution', transChoiceKeyIndex: 1, href: route('institution.index') },
            {
                transChoiceKey: 'department',
                href: route('institution-departments.index', { is_academic: department.value.attributes?.isAcademic }),
            },
            { title: department.value.attributes.departmentCode, href: departmentShowUrl },
            { title: level.value.attributes.level, href: departmentShowUrl },
            { title: course.value.attributes.course, href: departmentShowUrl },
            { title: mode.value.attributes.name, href: departmentShowUrl },
            { title: academicCalendarClass.value.name, href: classShowUrl.value },
            { title: student.value.name },
            { transChoiceKey: 'academic_calendar.course_work', transChoiceKeyIndex: 0 },
        ];
    });

    return {
        classShowUrl,
        breadcrumbs,
    };
}

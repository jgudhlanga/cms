import type { AcademicCalendar, AcademicCalendarClassDetail, ClassConfig } from '@/types/academic-calendar';
import type { DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import type { InstitutionDepartment, ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import type { Ref } from 'vue';
import { computed } from 'vue';

export function useDepartmentAcademicCalendarClassNavigation(
    department: Ref<InstitutionDepartment>,
    academicCalendar: Ref<AcademicCalendar>,
    course: Ref<DepartmentCourse>,
    level: Ref<DepartmentLevel>,
    mode: Ref<ModeOfStudy>,
    classConfig: Ref<ClassConfig | null>,
    academicCalendarClass: Ref<AcademicCalendarClassDetail>,
) {
    const departmentClassesUrl = computed(() =>
        route('academic-calendars.department-classes', {
            institution_department: String(department.value.id),
            academic_calendar: String(academicCalendar.value.id),
            department_level_id: String(level.value.id),
            department_course_id: String(course.value.id),
            mode_of_study_id: String(mode.value.id),
            ...(classConfig.value?.id ? { class_config_id: String(classConfig.value.id) } : {}),
        }),
    );

    const moveStudentsUrl = computed(() =>
        route('academic-calendars.department-classes.move-students', {
            institution_department: String(department.value.id),
            academic_calendar: String(academicCalendar.value.id),
            academic_calendar_class: String(academicCalendarClass.value.id),
        }),
    );

    const updateClassUrl = computed(() =>
        route('academic-calendars.department-classes.update', {
            institution_department: String(department.value.id),
            academic_calendar: String(academicCalendar.value.id),
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
            { transChoiceKey: 'class', href: departmentClassesUrl.value },
            { title: academicCalendarClass.value.name },
        ];
    });

    return {
        departmentClassesUrl,
        moveStudentsUrl,
        updateClassUrl,
        breadcrumbs,
    };
}

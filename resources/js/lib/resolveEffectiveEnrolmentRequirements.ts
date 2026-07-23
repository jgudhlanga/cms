import type { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';

type EnrolmentRequirement = CourseRequirement | DepartmentLevelRequirement;

const isTruthyFlag = (value: unknown): boolean => {
    const normalized = value?.toString();
    return normalized === '1' || normalized === 'true' || normalized === 'yes';
};

const hasRequirementId = (requirement: EnrolmentRequirement | null | undefined): boolean =>
    Number(String(requirement?.id ?? 0)) > 0;

const hasRequiredLevel = (requirement: EnrolmentRequirement | null | undefined): boolean =>
    Number(String(requirement?.attributes?.requiredLevelId ?? 0)) > 0;

/**
 * Merge course + department-level enrolment requirements for create/confirm UI.
 * Mirrors backend O-level precedence and keeps previous-level / SDP flags in scope
 * even when they live on the sibling requirement row.
 */
export function resolveEffectiveEnrolmentRequirements(
    courseReq: CourseRequirement | null | undefined,
    levelReq: DepartmentLevelRequirement | null | undefined,
): EnrolmentRequirement | null {
    const hasCourse = hasRequirementId(courseReq);
    const hasLevel = hasRequirementId(levelReq);

    if (!hasCourse && !hasLevel) {
        return null;
    }

    const courseOLevel = hasCourse && isTruthyFlag(courseReq?.attributes?.isOLevelRequired);
    const levelOLevel = hasLevel && isTruthyFlag(levelReq?.attributes?.isOLevelRequired);
    const oLevelSource = courseOLevel ? courseReq! : levelOLevel ? levelReq! : null;

    const coursePrev = hasCourse && hasRequiredLevel(courseReq);
    const levelPrev = hasLevel && hasRequiredLevel(levelReq);
    const prevSource = coursePrev ? courseReq! : levelPrev ? levelReq! : null;

    const onlyReadWriteRequired =
        (hasCourse && isTruthyFlag(courseReq?.attributes?.onlyReadWriteRequired)) ||
        (hasLevel && isTruthyFlag(levelReq?.attributes?.onlyReadWriteRequired));

    const base = oLevelSource ?? prevSource ?? (hasCourse ? courseReq! : levelReq!);

    const oLevelAttributes = oLevelSource
        ? {
              isOLevelRequired: true as const,
              requiredSubjectsCount: oLevelSource.attributes.requiredSubjectsCount,
              mainSubjectsCount: oLevelSource.attributes.mainSubjectsCount,
              mainSubjectIds: oLevelSource.attributes.mainSubjectIds,
              otherSubjectsCount: oLevelSource.attributes.otherSubjectsCount,
          }
        : {
              isOLevelRequired: false as const,
              requiredSubjectsCount: base.attributes.requiredSubjectsCount,
              mainSubjectsCount: base.attributes.mainSubjectsCount,
              mainSubjectIds: base.attributes.mainSubjectIds,
              otherSubjectsCount: base.attributes.otherSubjectsCount,
          };

    return {
        ...base,
        attributes: {
            ...base.attributes,
            ...oLevelAttributes,
            requiredLevelId: prevSource?.attributes.requiredLevelId ?? null,
            requiredLevel: prevSource?.attributes.requiredLevel ?? null,
            onlyReadWriteRequired,
        },
        relationships: oLevelSource?.relationships ?? base.relationships,
    };
}

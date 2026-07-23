import { describe, expect, it } from 'vitest';
import { resolveEffectiveEnrolmentRequirements } from '@/lib/resolveEffectiveEnrolmentRequirements';
import type { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';

const levelRequirement = (overrides: Partial<DepartmentLevelRequirement['attributes']> = {}): DepartmentLevelRequirement => ({
    type: 'department-level-requirement',
    id: 10,
    attributes: {
        departmentLeveId: 1,
        isOLevelRequired: false,
        requiredLevelId: null,
        requiredLevel: null,
        onlyReadWriteRequired: false,
        ...overrides,
    },
    relationships: {
        subjects: [{ id: 100, attributes: { name: 'Maths' } } as never],
    },
});

const courseRequirement = (overrides: Partial<CourseRequirement['attributes']> = {}): CourseRequirement => ({
    type: 'course-requirement',
    id: 20,
    attributes: {
        departmentLeveId: 1,
        departmentCourseId: 2,
        isOLevelRequired: false,
        requiredLevelId: null,
        requiredLevel: null,
        onlyReadWriteRequired: false,
        ...overrides,
    },
    relationships: {
        subjects: [{ id: 200, attributes: { name: 'English' } } as never],
    },
});

describe('resolveEffectiveEnrolmentRequirements', () => {
    it('returns null when neither requirement exists', () => {
        expect(resolveEffectiveEnrolmentRequirements(null, null)).toBeNull();
    });

    it('uses course O-level when course requires O-levels', () => {
        const course = courseRequirement({
            isOLevelRequired: true,
            mainSubjectsCount: 5,
            mainSubjectIds: [200],
        });
        const level = levelRequirement({
            isOLevelRequired: true,
            mainSubjectsCount: 3,
            mainSubjectIds: [100],
            requiredLevelId: 7,
            requiredLevel: 'NC',
        });

        const effective = resolveEffectiveEnrolmentRequirements(course, level);

        expect(effective?.attributes.isOLevelRequired).toBe(true);
        expect(effective?.attributes.mainSubjectsCount).toBe(5);
        expect(effective?.attributes.mainSubjectIds).toEqual([200]);
        expect(effective?.relationships?.subjects?.[0]?.id).toBe(200);
        expect(effective?.attributes.requiredLevelId).toBe(7);
        expect(effective?.attributes.requiredLevel).toBe('NC');
    });

    it('falls through to level O-level when course row exists but does not require O-levels', () => {
        const course = courseRequirement({ isOLevelRequired: false });
        const level = levelRequirement({
            isOLevelRequired: true,
            mainSubjectsCount: 4,
            mainSubjectIds: [100],
        });

        const effective = resolveEffectiveEnrolmentRequirements(course, level);

        expect(effective?.attributes.isOLevelRequired).toBe(true);
        expect(effective?.attributes.mainSubjectsCount).toBe(4);
        expect(effective?.relationships?.subjects?.[0]?.id).toBe(100);
    });

    it('keeps previous-level from level when course has no requiredLevelId', () => {
        const course = courseRequirement({ isOLevelRequired: false });
        const level = levelRequirement({
            requiredLevelId: 5,
            requiredLevel: 'NC',
        });

        const effective = resolveEffectiveEnrolmentRequirements(course, level);

        expect(effective?.attributes.requiredLevelId).toBe(5);
        expect(effective?.attributes.requiredLevel).toBe('NC');
    });

    it('prefers course previous-level when configured', () => {
        const course = courseRequirement({
            requiredLevelId: 9,
            requiredLevel: 'ND',
        });
        const level = levelRequirement({
            requiredLevelId: 5,
            requiredLevel: 'NC',
        });

        const effective = resolveEffectiveEnrolmentRequirements(course, level);

        expect(effective?.attributes.requiredLevelId).toBe(9);
        expect(effective?.attributes.requiredLevel).toBe('ND');
    });

    it('sets SDP when either course or level requires it', () => {
        const course = courseRequirement({ onlyReadWriteRequired: false });
        const level = levelRequirement({ onlyReadWriteRequired: true });

        expect(resolveEffectiveEnrolmentRequirements(course, level)?.attributes.onlyReadWriteRequired).toBe(true);
        expect(
            resolveEffectiveEnrolmentRequirements(courseRequirement({ onlyReadWriteRequired: true }), levelRequirement())
                ?.attributes.onlyReadWriteRequired,
        ).toBe(true);
    });
});

import { beforeEach, describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

import {
    clearProgrammeSelections,
    useReturningApplicationPrefill,
} from '@/composables/students/useReturningApplicationPrefill';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';

describe('useReturningApplicationPrefill', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('applies profile fields but leaves programme selections empty', () => {
        const store = useCreateApplicationFormStore();
        const storeRefs = {
            email: ref(store.email),
            first_name: ref(store.first_name),
            title: ref(store.title),
            department: ref(store.department),
            level: ref(store.level),
            course: ref(store.course),
            modeOfStudy: ref(store.modeOfStudy),
            department_id: ref(store.department_id),
            level_id: ref(store.level_id),
            course_id: ref(store.course_id),
            mode_of_study_id: ref(store.mode_of_study_id),
            levelRequirements: ref(store.levelRequirements),
            courseRequirements: ref(store.courseRequirements),
        };

        const prefill = {
            email: 'student@example.com',
            first_name: 'Takudzwa',
            title: { value: 1, label: 'Mr' },
            department_id: 10,
            level_id: 20,
            course_id: 30,
            mode_of_study_id: 1,
            department: { value: 10, label: 'Civil Engineering' },
            level: { value: 20, label: 'ND' },
            course: { value: 30, label: 'Civil Engineering' },
            modeOfStudy: { value: 1, label: 'Full Time' },
        };

        const { applyPrefill } = useReturningApplicationPrefill(prefill, storeRefs);
        applyPrefill();

        expect(storeRefs.email.value).toBe('student@example.com');
        expect(storeRefs.first_name.value).toBe('Takudzwa');
        expect(storeRefs.title.value).toEqual({ value: '1', label: 'Mr' });
        expect(storeRefs.department.value).toBeNull();
        expect(storeRefs.level.value).toBeNull();
        expect(storeRefs.course.value).toBeNull();
        expect(storeRefs.modeOfStudy.value).toBeNull();
        expect(storeRefs.department_id.value).toBeNull();
        expect(storeRefs.level_id.value).toBeNull();
        expect(storeRefs.course_id.value).toBeNull();
        expect(storeRefs.mode_of_study_id.value).toBeNull();
    });
});

describe('clearProgrammeSelections', () => {
    it('clears programme-related store refs', () => {
        const storeRefs = {
            department: ref({ value: '10', label: 'Civil Engineering' }),
            level: ref({ value: '20', label: 'ND' }),
            course: ref({ value: '30', label: 'Civil Engineering' }),
            modeOfStudy: ref({ value: '1', label: 'Full Time' }),
            department_id: ref(10),
            level_id: ref(20),
            course_id: ref(30),
            mode_of_study_id: ref(1),
            required_level_completed: ref(true),
            read_write_acknowledged: ref(true),
            levelRequirements: ref({ id: 1 }),
            courseRequirements: ref({ id: 2 }),
        };

        clearProgrammeSelections(storeRefs);

        expect(storeRefs.department.value).toBeNull();
        expect(storeRefs.level.value).toBeNull();
        expect(storeRefs.course.value).toBeNull();
        expect(storeRefs.modeOfStudy.value).toBeNull();
        expect(storeRefs.department_id.value).toBeNull();
        expect(storeRefs.level_id.value).toBeNull();
        expect(storeRefs.course_id.value).toBeNull();
        expect(storeRefs.mode_of_study_id.value).toBeNull();
        expect(storeRefs.required_level_completed.value).toBeNull();
        expect(storeRefs.read_write_acknowledged.value).toBeNull();
        expect(storeRefs.levelRequirements.value).toBeNull();
        expect(storeRefs.courseRequirements.value).toBeNull();
    });
});

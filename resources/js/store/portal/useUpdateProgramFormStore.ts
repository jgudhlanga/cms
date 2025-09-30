import { ProgramParams } from '@/types/portal';
import { defineStore } from 'pinia';

export const useUpdateProgramFormStore = defineStore('update-program-form', {
    state: (): ProgramParams => {
        return {
            modeOfStudy: null,
            mode_of_study_id: null,
            department: null,
            department_id: null,
            course: null,
            course_id: null,
            level: null,
            level_id: null,
            levelRequirements: null,
            required_level_completed: null,
            required_level_upload: null,
            read_write_acknowledged: null,
            o_level_subject_ids: null,
            o_level_years: null,
            o_level_sittings: null,
            o_level_other_subject_ids: null,
            o_level_other_grade_ids: null,
            o_level_other_years: null,
            o_level_other_sittings: null,
        };
    },
    persist: true,
});

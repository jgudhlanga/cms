import { defineStore } from 'pinia';

export type IStudentStore = {
    studentRefreshKey: number;
};

export const useStudentsStore = defineStore('students-store', {
    state: (): IStudentStore => {
        return {
            studentRefreshKey: 0,
        };
    },
    actions: {
        refreshStudents() {
            this.studentRefreshKey++;
        },
    },
});
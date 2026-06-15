import { defineStore } from 'pinia';

export type IStudentStore = {
    studentRefreshKey: number;
    activeTab: string;
};

export const useStudentsStore = defineStore('students-store', {
    state: (): IStudentStore => {
        return {
            studentRefreshKey: 0,
            activeTab: 'basic_info',
        };
    },
    actions: {
        refreshStudents() {
            this.studentRefreshKey++;
        },
    },
    persist: true,
});
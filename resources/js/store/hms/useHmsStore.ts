import { defineStore } from 'pinia';

export type IHmsTabsStore = {
    activeTab: string;
    hostelRefreshKey: number;
    roomRefreshKey: number;
    studentRefreshKey: number;
    applicationRefreshKey: number;
};

export const useHmsStore = defineStore('hms-store', {
    state: (): IHmsTabsStore => {
        return {
            activeTab: 'hostels',
            hostelRefreshKey: 0,
            roomRefreshKey: 0,
            studentRefreshKey: 0,
            applicationRefreshKey: 0,
        };
    },
    actions: {
        refreshHostels() {
            this.hostelRefreshKey++;
        },
        refreshRooms() {
            this.roomRefreshKey++;
        },
        refreshStudents() {
            this.studentRefreshKey++;
        },
        refreshApplications() {
            this.applicationRefreshKey++;
        },
    },
    persist: {
        pick: ['activeTab'],
    },
});

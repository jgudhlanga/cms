import { useStaff } from '@/composables/institution/useStaff';
import { Staff } from '@/types/staff';
import { SelectOption } from '@/types/utils';
import { computed, onMounted, type Ref, watch } from 'vue';

const academicStaffRoleSlugs = 'lecturer,senior-lecturer,lecturer-in-charge,head-of-department';

export function buildAcademicStaffUrl(institutionDepartmentId: number | string): string {
    return `${route('v1.department-metadata.staff', String(institutionDepartmentId))}?page_size=all&only[roles][]=${academicStaffRoleSlugs}`;
}

export function useAcademicStaffOptions(institutionDepartmentId: Ref<number | string> | number | string) {
    const { isLoading, loadStaff, staff } = useStaff();

    const departmentId = computed(() =>
        typeof institutionDepartmentId === 'object' && institutionDepartmentId !== null && 'value' in institutionDepartmentId
            ? institutionDepartmentId.value
            : institutionDepartmentId,
    );

    const staffUrl = computed(() => buildAcademicStaffUrl(departmentId.value));

    const load = async (): Promise<void> => {
        await loadStaff(staffUrl.value);
    };

    onMounted(() => {
        void load();
    });

    watch(staffUrl, () => {
        void load();
    });

    const options = computed((): SelectOption[] =>
        staff.value?.data?.map(
            (member: Staff) =>
                ({
                    value: Number(member.id),
                    label: member?.relationships?.user?.attributes?.name ?? '',
                }) satisfies SelectOption,
        ) ?? [],
    );

    return {
        options,
        isLoading,
        loadStaff: load,
        staffUrl,
    };
}

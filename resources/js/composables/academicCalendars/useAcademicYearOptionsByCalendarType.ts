import HttpService from '@/services/http.service';
import type { SelectOption } from '@/types/utils';
import { ref } from 'vue';

type AcademicYearOptionApiRow = {
    id: number;
    attributes: {
        name: string;
    };
};

/** When level has no calendar type, semester options are loaded (matches backend default). */
export function useAcademicYearOptionsByCalendarType() {
    const yearOptions = ref<SelectOption[]>([]);
    const yearOptionsLoading = ref(false);

    const loadYearOptions = async (calendarType: string | null | undefined): Promise<void> => {
        yearOptionsLoading.value = true;
        yearOptions.value = [];
        const ct = (calendarType ?? 'semester').trim() || 'semester';
        try {
            const base = route('v1.academic-year-options.index');
            const sep = base.includes('?') ? '&' : '?';
            const url = `${base}${sep}calendar_type=${encodeURIComponent(ct)}&page_size=all`;
            const body = await HttpService.get(url);
            const rows = (body?.data ?? []) as AcademicYearOptionApiRow[];
            yearOptions.value = rows.map((row) => ({
                value: String(row.id),
                label: row.attributes?.name ?? '',
            }));
        } catch {
            yearOptions.value = [];
        } finally {
            yearOptionsLoading.value = false;
        }
    };

    return {
        yearOptions,
        yearOptionsLoading,
        loadYearOptions,
    };
}

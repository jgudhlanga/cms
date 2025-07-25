import { errorAlert } from '@/lib/alerts';
import { buildFormOptions } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useInstitutionDepartmentMetadata = () => {
    const isLoading = ref(false);

    const getName = () => {
        return trans('trans.class_sizes');
    };
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveClassSizes = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            form.post(route('class-sizes.store', institutionDepartmentId), buildFormOptions(form, successMessage(), errorMessage()));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const loadDepartmentMetadata = async (url: string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        loadDepartmentMetadata,
        saveClassSizes,
    };
};

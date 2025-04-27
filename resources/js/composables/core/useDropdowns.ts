import { ref } from 'vue';
import HttpService from '@/services/http.service';
import { errorAlert } from '@/lib/alerts';
import { trans, trans_choice } from 'laravel-vue-i18n';

interface DropdownFetchData {
	url: string;
	search?: string;
	transKey?: string;
	transChoiceKey?: string;
}
export const useDropdowns = () => {

	const data = ref<Array<any>>([]);

	const fetchData = async ({ url, search, transKey, transChoiceKey }: DropdownFetchData) => {
		try {
			const response = await HttpService.get(`${url}?search=${search || ''}`, false);
			data.value = response.data;
		} catch (error: any) {
			const transValue = transKey ? trans(transKey) : transChoiceKey ? trans_choice(transChoiceKey, 2) : '';
			errorAlert(trans('trans.load_data_failure', { data: transValue }));
		}
	};

	return {
		fetchData,
		data,
	};
};

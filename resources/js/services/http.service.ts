import { AxiosRequestConfig } from 'axios';
import customAxios from '@/services/http-init';
import { API_BASE_URL } from '@/lib/constants';

export interface IHttpService {
	get: (url: string, authorize?: boolean) => Promise<any>;
	post: (url: string, data: any, authorize?: boolean) => Promise<any>;
	put: (url: string, data: any, authorize: boolean) => Promise<any>;
	delete: (url: string, authorize: boolean) => Promise<any>;
	baseUrl: () => string;
}

class HttpService implements IHttpService {

	baseUrl() {
		return API_BASE_URL;
	}

	async delete(url: string, authorize: boolean): Promise<any> {
		try {
			return await customAxios(this.baseUrl()).delete(url, this.setOptions(authorize));
		} catch (error) {
			throw error;
		}
	}

	async get(url: string, authorize: boolean | undefined): Promise<any> {
		try {
			const response = await customAxios(this.baseUrl()).get(url, this.setOptions(authorize));
			return response.data;
		} catch (error) {
			throw error;
		}
	}

	async post(url: string, data: any, authorize: boolean | undefined): Promise<any> {
		try {
			const response = await customAxios(this.baseUrl()).post(url, data, this.setOptions(authorize));
			return response.data;
		} catch (error) {
			throw error;
		}
	}

	async put(url: string, data: any, authorize: boolean): Promise<any> {
		try {
			const response = await customAxios(this.baseUrl()).put(url, data, this.setOptions(authorize));
			return response.data;
		} catch (error) {
			throw error;
		}
	}

	setOptions(authorize?: boolean) {
		const options: AxiosRequestConfig = {
			headers: {
				authorize: authorize
			}
		};
		return options;
	}
}

export default new HttpService();

import { API_BASE_URL } from '@/lib/constants';
import customAxios from '@/services/http-init';

export interface IHttpService {
    get: (url: string) => Promise<any>;
    post: (url: string, data: any) => Promise<any>;
    put: (url: string, data: any) => Promise<any>;
    delete: (url: string) => Promise<any>;
    baseUrl: () => string;
}

class HttpService implements IHttpService {
    baseUrl() {
        return API_BASE_URL;
    }

    async delete(url: string): Promise<any> {
        try {
            return await customAxios(this.baseUrl()).delete(url);
        } catch (error) {
            throw error;
        }
    }

    async get(url: string): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).get(url);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async post(url: string, data: any): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).post(url, data);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async put(url: string, data: any): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).put(url, data);
            return response.data;
        } catch (error) {
            throw error;
        }
    }
}

export default new HttpService();

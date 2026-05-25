import { API_BASE_URL } from '@/lib/constants';
import customAxios from '@/services/http-init';
import { AxiosRequestConfig } from 'axios';

export interface IHttpService {
    get: (url: string, config?: AxiosRequestConfig) => Promise<any>;
    post: (url: string, data: any, config?: AxiosRequestConfig) => Promise<any>;
    put: (url: string, data: any, config?: AxiosRequestConfig) => Promise<any>;
    patch: (url: string, data: any, config?: AxiosRequestConfig) => Promise<any>;
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

    async get(url: string, config?: AxiosRequestConfig): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).get(url, config);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async post(url: string, data: any, config?: AxiosRequestConfig): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).post(url, data, config);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async put(url: string, data: any, config?: AxiosRequestConfig): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).put(url, data, config);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    async patch(url: string, data: any, config?: AxiosRequestConfig): Promise<any> {
        try {
            const response = await customAxios(this.baseUrl()).patch(url, data, config);
            return response.data;
        } catch (error) {
            throw error;
        }
    }
}

export default new HttpService();

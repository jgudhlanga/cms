import axios, { type AxiosInstance } from 'axios';

// One configured instance per base URL; creating one per request re-registers interceptors every call.
const instances = new Map<string, AxiosInstance>();

const customAxios = (url: string): AxiosInstance => {
    const existing = instances.get(url);

    if (existing) {
        return existing;
    }

    const instance = axios.create({
        baseURL: url,
        withCredentials: true,
        xsrfCookieName: 'XSRF-TOKEN',
        xsrfHeaderName: 'X-XSRF-TOKEN',
        headers: {
            'Content-type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    instance.interceptors.request.use(
        (config) => {
            return config;
        },
        (error) => {
            return Promise.reject(error);
        },
    );

    instance.interceptors.response.use(
        (config) => {
            return config;
        },
        async (error) => {
            if (error.response && error.response.status === 401) {
                return Promise.reject(error);
            }
            return Promise.reject(error);
        },
    );

    instances.set(url, instance);

    return instance;
};

export default customAxios;

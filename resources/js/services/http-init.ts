import axios from 'axios';

const customAxios = (url: string) => {
    const instance = axios.create({
        baseURL: url,
        withCredentials: true,
        xsrfCookieName: 'XSRF-TOKEN',
        xsrfHeaderName: 'X-XSRF-TOKEN',
        headers: {
            'Content-type': 'application/json',
            Accept: 'application/json',
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

    return instance;
};

export default customAxios;

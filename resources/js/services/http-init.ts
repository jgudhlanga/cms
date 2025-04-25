import axios from 'axios';


const customAxios = (url: string) => {
	let instance = axios.create({
		baseURL: url,
		headers: {
			'Content-type': 'application/json'
		}
	});

	instance.interceptors.request.use(
		config => {
			if (config.headers.has('authorize') && config.headers.authorize) {
				/* const {authToken, isLoggedIn} = useAuth();
				const token = isLoggedIn() ? authToken.value?.attributes.accessToken : '';
				config.headers.Authorization = `Bearer ${token}`; */
			}
			return config;
		},
		error => {
			return Promise.reject(error);
		});

	instance.interceptors.response.use(
		config => {
			return config;
		},
		async error => {
			/* const {logout} = useAuth(); */
			if (error.response && error.response.status === 401) {
				//await logout();
				return Promise.reject(error);
			}
			return Promise.reject(error);
		});

	return instance;
};

export default customAxios;

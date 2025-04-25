const DEFAULT_AVATAR: string = '/assets/images/user.png';
const DEFAULT_IMAGE: string = '/assets/images/object.svg';
const LOGO: string = '/assets/images/logo.jpeg';
const API_BASE_URL = 'https://havasoft.test/api';
const API_VERSION = 'v1';

const APP_MODULE_KEYS = {
	modules: 'modules',
	permissions: 'permissions',
	roles: 'roles',
	role_permissions: 'role_permissions',
	bank_account_types: 'bank_account_types',
	bank_branches: 'bank_branches',
	banks: 'banks',
	communication_methods: 'communication_methods',
	countries: 'countries',
	genders: 'genders',
	languages: 'languages',
	payment_days: 'payment_days',
	payment_frequencies: 'payment_frequencies',
	payment_methods: 'payment_methods',
	premium_age_bands: 'premium_age_bands',
	premium_coverage_amounts: 'premium_coverage_amounts',
	premium_coverage_groups: 'premium_coverage_groups',
	premium_main_member_age_ranges: 'premium_main_member_age_ranges',
	member_types: 'member_types',
	member_type_rules: 'member_type_rules',
	package_member_types: 'package_member_types',
	package_premiums: 'package_premiums',
	packages: 'packages',
	product_packages: 'product_packages',
	product_type_products: 'product_type_products',
	product_types: 'product_types',
	products: 'products',
	product_rules: 'product_rules',
	product_groups_config: 'product_groups_config',
	package_premium_config: 'package_premium_config',
	package_premium_create_update: 'package_premium_create_update',
	provinces: 'provinces',
	races: 'races',
	statuses: 'statuses',
	trading_statuses: 'trading_statuses',
	titles: 'titles',
	insurers: 'insurers',
	address_types: 'address_types',
	scheme_branches: 'scheme_branches',
	bank_details: 'bank_details',
	contacts: 'contacts',
	addresses: 'addresses',
};

export { API_BASE_URL, API_VERSION, DEFAULT_AVATAR, DEFAULT_IMAGE, LOGO, APP_MODULE_KEYS };

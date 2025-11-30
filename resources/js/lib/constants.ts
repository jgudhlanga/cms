import { RadioGroupOption } from '@/types/forms';

const DEFAULT_AVATAR: string = '/assets/images/user.png';
const DEFAULT_IMAGE: string = '/assets/images/object.svg';
const LOGO: string = '/assets/images/logo.jpeg';
const PAYMENT_METHODS: string = '/assets/images/payment_methods.png';
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;
const API_VERSION = 'v1';

const PAGINATION_ITEMS_PER_PAGE = 15;
const PAGINATION_MAX_LIMIT = 1000;
const APP_MODULE_KEYS = {
    modules: 'modules',
    permissions: 'permissions',
    roles: 'roles',
    role_permissions: 'role_permissions',
    communication_methods: 'communication_methods',
    countries: 'countries',
    genders: 'genders',
    languages: 'languages',
    payment_days: 'payment_days',
    payment_frequencies: 'payment_frequencies',
    payment_methods: 'payment_methods',
    provinces: 'provinces',
    races: 'races',
    statuses: 'statuses',
    titles: 'titles',
    insurers: 'insurers',
    address_types: 'address_types',
    contacts: 'contacts',
    addresses: 'addresses',
    relationships: 'relationships',
    courses: 'courses',
    departments: 'departments',
    divisions: 'divisions',
    grades: 'grades',
    levels: 'levels',
    modes_of_study: 'modes_of_study',
    subjects: 'subjects',
    districts: 'districts',
    institution_departments: 'institution_departments',
    department_levels: 'department_levels',
    department_courses: 'department_courses',
    marital_statuses: 'marital_statuses',
    religions: 'religions',
    academic_levels: 'academic_levels',
    sponsor_types: 'sponsor_types',
    sponsors: 'sponsors',
    academic_records: 'academic_records',
    workflow_steps: 'workflow_steps',
    workflow_step_actions: 'workflow_step_actions',
    intake_periods: 'intake_periods',
    employment_types: 'employment_types',
    id_types: 'id_types',
    department_application_steps: 'department_application_steps',
    role_groups: 'role_groups',
    department_workflow_actions: 'department_workflow_actions',
    next_of_kin: 'next_of_kin',
    student_personal_details: 'student_personal_details',
    upload_proof_of_payment: 'upload_proof_of_payment',
    preview_payment_proof: 'preview_payment_proof',
    document_types: 'document_types',
    fee_types: 'fee_types',
    fee_structures: 'fee_structures',
    show_payment_status: 'show_payment_status',
    o_level_subjects: 'o_level_subjects',
};
const EXAM_SITTINGS = [
    { value: 'june', label: 'June' },
    { value: 'november', label: 'November' },
    { value: 'other', label: 'Other' },
];
const DISABILITY_OPTIONS: RadioGroupOption[] = [
    { label: 'Yes', value: 'yes', inputId: 'disability_yes' },
    { label: 'No', value: 'no', inputId: 'disability_no' },
    { label: 'Skip', value: 'prefer_not_to_say', inputId: 'disability_prefer_not_to_say' },
];
const ACADEMIC_YEAR_START = 2026;
export {
    API_BASE_URL,
    API_VERSION,
    APP_MODULE_KEYS,
    DEFAULT_AVATAR,
    DEFAULT_IMAGE,
    DISABILITY_OPTIONS,
    EXAM_SITTINGS,
    LOGO,
    PAGINATION_ITEMS_PER_PAGE,
    PAGINATION_MAX_LIMIT,
    PAYMENT_METHODS,
    ACADEMIC_YEAR_START,
};


<?php

namespace App\Enums\Acl;

enum PermissionEnum: string
{
    /** Acl View */
    case VIEW_ACL_INDEX = 'view:acl-settings';
    /** Acl Modules*/
    case VIEW_ANY_MODULES = 'viewAny:modules';
    case VIEW_MODULE = 'view:modules';
    case CREATE_MODULE = 'create:modules';
    case UPDATE_MODULE = 'update:modules';
    case DELETE_MODULE = 'delete:modules';
    case RESTORE_MODULE = 'restore:modules';
    case FORCE_DELETE_MODULE = 'forceDelete:modules';
    case IMPORT_MODULES = 'import:modules';
    case EXPORT_MODULES = 'export:modules';
    case VIEW_MODULES_AUDIT_TRAIL = 'viewAuditTrail:modules';
    /** Acl Roles*/
    case VIEW_ANY_ROLES = 'viewAny:roles';
    case VIEW_ROLE = 'view:roles';
    case CREATE_ROLE = 'create:roles';
    case UPDATE_ROLE = 'update:roles';
    case DELETE_ROLE = 'delete:roles';
    case RESTORE_ROLE = 'restore:roles';
    case FORCE_DELETE_ROLE = 'forceDelete:roles';
    case IMPORT_ROLES = 'import:roles';
    case EXPORT_ROLES = 'export:roles';
    case VIEW_ROLES_AUDIT_TRAIL = 'viewAuditTrail:roles';
    /*** Acl Permissions */
    case VIEW_ANY_PERMISSIONS = 'viewAny:permissions';
    case VIEW_PERMISSION = 'view:permissions';
    case CREATE_PERMISSION = 'create:permissions';
    case UPDATE_PERMISSION = 'update:permissions';
    case DELETE_PERMISSION = 'delete:permissions';
    case RESTORE_PERMISSION = 'restore:permissions';
    case FORCE_DELETE_PERMISSION = 'forceDelete:permissions';
    case IMPORT_PERMISSIONS = 'import:permissions';
    case EXPORT_PERMISSIONS = 'export:permissions';
    case VIEW_PERMISSIONS_AUDIT_TRAIL = 'viewAuditTrail:permissions';
    /** COMMUNICATIONS */
    case VIEW_ANY_COMMUNICATIONS = 'viewAny:communications';
    case VIEW_COMMUNICATION = 'view:communications';
    case CREATE_COMMUNICATION = 'create:communications';
    case UPDATE_COMMUNICATION = 'update:communications';
    case DELETE_COMMUNICATION = 'delete:communications';
    case RESTORE_COMMUNICATION = 'restore:communications';
    case FORCE_DELETE_COMMUNICATION = 'forceDelete:communications';
    case IMPORT_COMMUNICATIONS = 'import:communications';
    case EXPORT_COMMUNICATIONS = 'export:communications';
    case CRUD_COMMUNICATION_SETTINGS = 'crud-settings:communications';
    case VIEW_COMMUNICATION_AUDIT_TRAIL = 'viewAuditTrail:communications';
    /** DASHBOARD */
    case VIEW_ANY_DASHBOARD = 'viewAny:dashboards';
    case VIEW_DASHBOARD = 'view:dashboards';
    /** REPORTS */
    case VIEW_ANY_REPORTS = 'viewAny:reports';
    case VIEW_REPORT = 'view:reports';
    case CREATE_REPORT = 'create:reports';
    case UPDATE_REPORT = 'update:reports';
    case DELETE_REPORT = 'delete:reports';
    case RESTORE_REPORT = 'restore:reports';
    case FORCE_DELETE_REPORT = 'forceDelete:reports';
    case IMPORT_REPORTS = 'import:reports';
    case EXPORT_REPORTS = 'export:reports';
    /** TENANTS */
    case VIEW_ANY_TENANTS = 'viewAny:tenants';
    case VIEW_TENANT = 'view:tenants';
    case CREATE_TENANT = 'create:tenants';
    case UPDATE_TENANT = 'update:tenants';
    case DELETE_TENANT = 'delete:tenants';
    case RESTORE_TENANT = 'restore:tenants';
    case FORCE_DELETE_TENANT = 'forceDelete:tenants';
    case IMPORT_TENANTS = 'import:tenants';
    case EXPORT_TENANTS = 'export:tenants';
    case CRUD_TENANTS_SETTINGS = 'crud-settings:tenants';
    case VIEW_TENANTS_AUDIT_TRAIL = 'viewAuditTrail:tenants';
    /** USERS */
    case VIEW_ANY_USERS = 'viewAny:users';
    case VIEW_USERS = 'view:users';
    case CREATE_USERS = 'create:users';
    case UPDATE_USERS = 'update:users';
    case DELETE_USERS = 'delete:users';
    case RESTORE_USERS = 'restore:users';
    case FORCE_DELETE_USERS = 'forceDelete:users';
    case IMPORT_USERS = 'import:users';
    case EXPORT_USERS = 'export:users';
    case CRUD_USERS_SETTINGS = 'crud-settings:users';
    case VIEW_USERS_AUDIT_TRAIL = 'viewAuditTrail:users';
    /** SETTINGS MANAGEMENT */
    case VIEW_SETTINGS = 'view:settings';
    case CREATE_SETTINGS = 'create:settings';
    case UPDATE_SETTINGS = 'update:settings';
    case DELETE_SETTINGS = 'delete:settings';
    case RESTORE_SETTINGS = 'restore:settings';
    case FORCE_DELETE_SETTINGS = 'forceDelete:settings';
    case IMPORT_SETTINGS = 'import:settings';
    case EXPORT_SETTINGS = 'export:settings';
    case VIEW_SETTINGS_AUDIT_TRAIL = 'viewAuditTrail:settings';

    /** INSTITUTION SETTINGS */
    case VIEW_INSTITUTION_SETTINGS = 'view:institution-settings';
    case CREATE_INSTITUTION_SETTINGS = 'create:institution-settings';
    case UPDATE_INSTITUTION_SETTINGS = 'update:institution-settings';
    case DELETE_INSTITUTION_SETTINGS = 'delete:institution-settings';
    case RESTORE_INSTITUTION_SETTINGS = 'restore:institution-settings';
    case FORCE_DELETE_INSTITUTION_SETTINGS = 'forceDelete:institution-settings';
    case IMPORT_INSTITUTION_SETTINGS = 'import:institution-settings';
    case EXPORT_INSTITUTION_SETTINGS = 'export:institution-settings';
    case VIEW_INSTITUTION_SETTINGS_AUDIT_TRAIL = 'viewAuditTrail:institution-settings';
    /** INSTITUTION DEPARTMENTS */
    case VIEW_ANY_DEPARTMENT_METADATA = 'viewAny:department-metadata';
    case VIEW_DEPARTMENT_METADATA = 'view:department-metadata';
    case CREATE_DEPARTMENT_METADATA = 'create:department-metadata';
    case UPDATE_DEPARTMENT_METADATA = 'update:department-metadata';
    case DELETE_DEPARTMENT_METADATA = 'delete:department-metadata';
    case RESTORE_DEPARTMENT_METADATA = 'restore:department-metadata';
    case FORCE_DELETE_DEPARTMENT_METADATA = 'forceDelete:department-metadata';
    case IMPORT_DEPARTMENT_METADATA = 'import:department-metadata';
    case EXPORT_DEPARTMENT_METADATA = 'export:department-metadata';
    case VIEW_DEPARTMENT_METADATA_AUDIT_TRAIL = 'viewAuditTrail:department-metadata';
    case SETUP_DEPARTMENT_LEVELS = 'department-setup:levels';
    case SETUP_DEPARTMENT_COURSES = 'department-setup:courses';
    case SETUP_DEPARTMENT_CLASS_SIZES = 'department-setup:class-sizes';
    case SETUP_DEPARTMENT_WORKFLOWS = 'department-setup:workflows';
    case VIEW_ONLY_OWN_DEPARTMENT = 'viewOnlyOwnDepartment:departments';
    /** ROOT / GLOBAL USER */
    case ROOT_MANAGE = 'root:manage';
    /**OWN TENANT DATA MANAGE */
    case MANAGE_OWN_TENANT_DATA = 'manageOwnData:tenants';
    /** SHARED */
    case VIEW_ANY_BANK_DETAILS = 'viewAny:bank-details';
    case VIEW_BANK_DETAILS = 'view:bank-details';
    case CREATE_BANK_DETAILS = 'create:bank-details';
    case UPDATE_BANK_DETAILS = 'update:bank-details';
    case DELETE_BANK_DETAILS = 'delete:bank-details';
    case RESTORE_BANK_DETAILS = 'restore:bank-details';
    case FORCE_DELETE_BANK_DETAILS = 'forceDelete:bank-details';
    case IMPORT_BANK_DETAILS = 'import:bank-details';
    case EXPORT_BANK_DETAILS = 'export:bank-details';
    case VIEW_ANY_ADDRESSES = 'viewAny:addresses';
    case VIEW_ADDRESSES = 'view:addresses';
    case CREATE_ADDRESSES = 'create:addresses';
    case UPDATE_ADDRESSES = 'update:addresses';
    case DELETE_ADDRESSES = 'delete:addresses';
    case RESTORE_ADDRESSES = 'restore:addresses';
    case FORCE_DELETE_ADDRESSES = 'forceDelete:addresses';
    case IMPORT_ADDRESSES = 'import:addresses';
    case EXPORT_ADDRESSES = 'export:addresses';
    case VIEW_ANY_CONTACTS = 'viewAny:contacts';
    case VIEW_CONTACTS = 'view:contacts';
    case CREATE_CONTACTS = 'create:contacts';
    case UPDATE_CONTACTS = 'update:contacts';
    case DELETE_CONTACTS = 'delete:contacts';
    case RESTORE_CONTACTS = 'restore:contacts';
    case FORCE_DELETE_CONTACTS = 'forceDelete:contacts';
    case IMPORT_CONTACTS = 'import:contacts';
    case EXPORT_CONTACTS = 'export:contacts';
    case VIEW_ANY_NEXT_OF_KINS = 'viewAny:next-of-kins';
    case VIEW_NEXT_OF_KINS = 'view:next-of-kins';
    case CREATE_NEXT_OF_KINS = 'create:next-of-kins';
    case UPDATE_NEXT_OF_KINS = 'update:next-of-kins';
    case DELETE_NEXT_OF_KINS = 'delete:next-of-kins';
    case RESTORE_NEXT_OF_KINS = 'restore:next-of-kins';
    case FORCE_DELETE_NEXT_OF_KINS = 'forceDelete:next-of-kins';
    case IMPORT_NEXT_OF_KINS = 'import:next-of-kins';
    case EXPORT_NEXT_OF_KINS = 'export:next-of-kins';
    # PORTAL
    case VIEW_OWN_STUDENT_DASHBOARD = 'viewOwnDashboard:students';
    case MANAGE_OWN_STUDENT_PERSONAL_DETAILS = 'manageOwnStudentPersonalDetails:students';
    case MANAGE_OWN_STUDENT_PROGRAM_DETAILS = 'manageOwnStudentProgramDetails:students';
    case MANAGE_OWN_STUDENT_SPONSOR_DETAILS = 'manageOwnStudentSponsorDetails:students';
    case MANAGE_OWN_STUDENT_CONTACT_DETAILS = 'manageOwnStudentContactDetails:students';
    case MANAGE_OWN_STUDENT_FINANCIAL_DETAILS = 'manageOwnStudentFinancialDetails:students';
    case MANAGE_OWN_STUDENT_ACADEMIC_DETAILS = 'manageOwnStudentAcademicDetails:students';
    /** ADMIN STUDENTS */
    case MANAGE_STUDENTS_METADATA = 'manageStudentMetadata:admin';
    /** STUDENT_PROGRAMS*/
    case VIEW_ANY_STUDENT_PROGRAMS = 'viewAny:student-programs';
    case VIEW_STUDENT_PROGRAMS = 'view:student-programs';
    case CREATE_STUDENT_PROGRAMS = 'create:student-programs';
    case UPDATE_STUDENT_PROGRAMS = 'update:student-programs';
    case DELETE_STUDENT_PROGRAMS = 'delete:student-programs';
    case RESTORE_STUDENT_PROGRAMS = 'restore:student-programs';
    case FORCE_DELETE_STUDENT_PROGRAMS = 'forceDelete:student-programs';
    case IMPORT_STUDENT_PROGRAMS = 'import:student-programs';
    case EXPORT_STUDENT_PROGRAMS = 'export:student-programs';
    case CRUD_STUDENT_PROGRAMS_SETTINGS = 'crud-settings:student-programs';
    case VIEW_STUDENT_PROGRAMS_AUDIT_TRAIL = 'viewAuditTrail:student-programs';
    case VERIFY_CLASS_LIST = 'verify:class-list';
    case CREATE_CLASS_LIST = 'create:class-lists';
    case UPDATE_CLASS_LIST = 'update:class-lists';
    case DELETE_CLASS_LIST = 'delete:class-lists';
    case VIEW_CLASS_LIST = 'view:class-lists';
    /** STUDENTS */
    case VIEW_ANY_STUDENTS = 'viewAny:students';
    case VIEW_STUDENTS = 'view:students';
    case CREATE_STUDENTS = 'create:students';
    case UPDATE_STUDENTS = 'update:students';
    case DELETE_STUDENTS = 'delete:students';
    case RESTORE_STUDENTS = 'restore:students';
    case FORCE_DELETE_STUDENTS = 'forceDelete:students';
    case IMPORT_STUDENTS = 'import:students';
    case EXPORT_STUDENTS = 'export:students';
    case CRUD_STUDENTS_SETTINGS = 'crud-settings:students';
    case VIEW_STUDENTS_AUDIT_TRAIL = 'viewAuditTrail:students';
    /** EXAMINATIONS */
    case VIEW_ANY_EXAMINATION = 'viewAny:examinations';
    case VIEW_EXAMINATION = 'view:examinations';
    case CREATE_EXAMINATION = 'create:examinations';
    case UPDATE_EXAMINATION = 'update:examinations';
    case DELETE_EXAMINATION = 'delete:examinations';
    case RESTORE_EXAMINATION = 'restore:examinations';
    case FORCE_DELETE_EXAMINATION = 'forceDelete:examinations';
    case IMPORT_EXAMINATION = 'import:examinations';
    case EXPORT_EXAMINATION = 'export:examinations';
    case CRUD_EXAMINATION_SETTINGS = 'crud-settings:examinations';
    case VIEW_EXAMINATION_AUDIT_TRAIL = 'viewAuditTrail:examinations';
    /** ACCOMMODATIONS */
    case VIEW_ANY_ACCOMMODATIONS = 'viewAny:accommodations';
    case VIEW_ACCOMMODATIONS = 'view:accommodations';
    case CREATE_ACCOMMODATIONS = 'create:accommodations';
    case UPDATE_ACCOMMODATIONS = 'update:accommodations';
    case DELETE_ACCOMMODATIONS = 'delete:accommodations';
    case RESTORE_ACCOMMODATIONS = 'restore:accommodations';
    case FORCE_DELETE_ACCOMMODATIONS = 'forceDelete:accommodations';
    case IMPORT_ACCOMMODATIONS = 'import:accommodations';
    case EXPORT_ACCOMMODATIONS = 'export:accommodations';
    case CRUD_ACCOMMODATIONS_SETTINGS = 'crud-settings:accommodations';
    case VIEW_ACCOMMODATIONS_AUDIT_TRAIL = 'viewAuditTrail:accommodations';
    /** DOCUMENT TEMPLATES */
    case VIEW_ANY_DOCUMENT_TEMPLATES = 'viewAny:document-templates';
    case VIEW_DOCUMENT_TEMPLATES = 'view:document-templates';
    case CREATE_DOCUMENT_TEMPLATES = 'create:document-templates';
    case UPDATE_DOCUMENT_TEMPLATES = 'update:document-templates';
    case DELETE_DOCUMENT_TEMPLATES = 'delete:document-templates';
    case RESTORE_DOCUMENT_TEMPLATES = 'restore:document-templates';
    case FORCE_DELETE_DOCUMENT_TEMPLATES = 'forceDelete:document-templates';
    case IMPORT_DOCUMENT_TEMPLATES = 'import:document-templates';
    case EXPORT_DOCUMENT_TEMPLATES = 'export:document-templates';
    case CRUD_DOCUMENTS_SETTING_TEMPLATES = 'crud-settings:document-templates';
    case VIEW_DOCUMENT_TEMPLATES_AUDIT_TRAIL = 'viewAuditTrail:document-templates';
    /** FEE STRUCTURE */
    case VIEW_ANY_FEE_STRUCTURES = 'viewAny:fee-structures';
    case VIEW_FEE_STRUCTURES = 'view:fee-structures';
    case CREATE_FEE_STRUCTURES = 'create:fee-structures';
    case UPDATE_FEE_STRUCTURES = 'update:fee-structures';
    case DELETE_FEE_STRUCTURES = 'delete:fee-structures';
    case RESTORE_FEE_STRUCTURES = 'restore:fee-structures';
    case FORCE_DELETE_FEE_STRUCTURES = 'forceDelete:fee-structures';
    case IMPORT_FEE_STRUCTURES = 'import:fee-structures';
    case EXPORT_FEE_STRUCTURES = 'export:fee-structures';
    case CRUD_FEE_STRUCTURES = 'crud-settings:fee-structures';
    case VIEW_FEE_STRUCTURES_AUDIT_TRAIL = 'viewAuditTrail:fee-structures';

    public function label(): string
    {
        return match ($this) {
            /** Acl */
            self::VIEW_ACL_INDEX => 'view:acl-settings',
            /** Modules */
            self::VIEW_ANY_MODULES => 'viewAny:modules',
            self::VIEW_MODULE => 'view:modules',
            self::CREATE_MODULE => 'create:modules',
            self::UPDATE_MODULE => 'update:modules',
            self::DELETE_MODULE => 'delete:modules',
            self::RESTORE_MODULE => 'restore:modules',
            self::FORCE_DELETE_MODULE => 'forceDelete:modules',
            self::IMPORT_MODULES => 'import:modules',
            self::EXPORT_MODULES => 'export:modules',
            self::VIEW_MODULES_AUDIT_TRAIL => 'viewAuditTrail:modules',
            /** Roles */
            self::VIEW_ANY_ROLES => 'viewAny:roles',
            self::VIEW_ROLE => 'view:roles',
            self::CREATE_ROLE => 'create:roles',
            self::UPDATE_ROLE => 'update:roles',
            self::DELETE_ROLE => 'delete:roles',
            self::RESTORE_ROLE => 'restore:roles',
            self::FORCE_DELETE_ROLE => 'forceDelete:roles',
            self::IMPORT_ROLES => 'import:roles',
            self::EXPORT_ROLES => 'export:roles',
            self::VIEW_ROLES_AUDIT_TRAIL => 'viewAuditTrail:roles',
            /** Permissions */
            self::VIEW_ANY_PERMISSIONS => 'viewAny:permissions',
            self::VIEW_PERMISSION => 'view:permissions',
            self::CREATE_PERMISSION => 'create:permissions',
            self::UPDATE_PERMISSION => 'update:permissions',
            self::DELETE_PERMISSION => 'delete:permissions',
            self::RESTORE_PERMISSION => 'restore:permissions',
            self::FORCE_DELETE_PERMISSION => 'forceDelete:permissions',
            self::IMPORT_PERMISSIONS => 'import:permissions',
            self::EXPORT_PERMISSIONS => 'export:permissions',
            self::VIEW_PERMISSIONS_AUDIT_TRAIL => 'viewAuditTrail:permissions',
            /** COMMUNICATIONS */
            self::VIEW_ANY_COMMUNICATIONS => 'viewAny:communications',
            self::VIEW_COMMUNICATION => 'view:communications',
            self::CREATE_COMMUNICATION => 'create:communications',
            self::UPDATE_COMMUNICATION => 'update:communications',
            self::DELETE_COMMUNICATION => 'delete:communications',
            self::RESTORE_COMMUNICATION => 'restore:communications',
            self::FORCE_DELETE_COMMUNICATION => 'forceDelete:communications',
            self::IMPORT_COMMUNICATIONS => 'import:communications',
            self::EXPORT_COMMUNICATIONS => 'export:communications',
            self::CRUD_COMMUNICATION_SETTINGS => 'crud-settings:communications',
            self::VIEW_COMMUNICATION_AUDIT_TRAIL => 'viewAuditTrail:communications',
            /** DASHBOARD */
            self::VIEW_ANY_DASHBOARD => 'viewAny:dashboards',
            self::VIEW_DASHBOARD => 'view:dashboards',
            /** REPORTS */
            self::VIEW_ANY_REPORTS => 'viewAny:reports',
            self::VIEW_REPORT => 'view:reports',
            self::CREATE_REPORT => 'create:reports',
            self::UPDATE_REPORT => 'update:reports',
            self::DELETE_REPORT => 'delete:reports',
            self::RESTORE_REPORT => 'restore:reports',
            self::FORCE_DELETE_REPORT => 'forceDelete:reports',
            self::IMPORT_REPORTS => 'import:reports',
            self::EXPORT_REPORTS => 'export:reports',
            /** TENANTS */
            self::VIEW_ANY_TENANTS => 'viewAny:tenants',
            self::VIEW_TENANT => 'view:tenants',
            self::CREATE_TENANT => 'create:tenants',
            self::UPDATE_TENANT => 'update:tenants',
            self::DELETE_TENANT => 'delete:tenants',
            self::RESTORE_TENANT => 'restore:tenants',
            self::FORCE_DELETE_TENANT => 'forceDelete:tenants',
            self::IMPORT_TENANTS => 'import:tenants',
            self::EXPORT_TENANTS => 'export:tenants',
            self::CRUD_TENANTS_SETTINGS => 'crud-settings:tenants',
            self::VIEW_TENANTS_AUDIT_TRAIL => 'viewAuditTrail:tenants',
            /** USERS */
            self::VIEW_ANY_USERS => 'viewAny:users',
            self::VIEW_USERS => 'view:users',
            self::CREATE_USERS => 'create:users',
            self::UPDATE_USERS => 'update:users',
            self::DELETE_USERS => 'delete:users',
            self::RESTORE_USERS => 'restore:users',
            self::FORCE_DELETE_USERS => 'forceDelete:users',
            self::IMPORT_USERS => 'import:users',
            self::EXPORT_USERS => 'export:users',
            self::CRUD_USERS_SETTINGS => 'crud-settings:users',
            self::VIEW_USERS_AUDIT_TRAIL => 'viewAuditTrail:users',
            /** SETTINGS */
            self::VIEW_SETTINGS => 'view:settings',
            self::CREATE_SETTINGS => 'create:settings',
            self::UPDATE_SETTINGS => 'update:settings',
            self::DELETE_SETTINGS => 'delete:settings',
            self::RESTORE_SETTINGS => 'restore:settings',
            self::FORCE_DELETE_SETTINGS => 'forceDelete:settings',
            self::IMPORT_SETTINGS => 'import:settings',
            self::EXPORT_SETTINGS => 'export:settings',
            self::VIEW_SETTINGS_AUDIT_TRAIL => 'viewAuditTrail:settings',
            /** INSTITUTION SETTINGS */
            self::VIEW_INSTITUTION_SETTINGS => 'view:institution-settings',
            self::CREATE_INSTITUTION_SETTINGS => 'create:institution-settings',
            self::UPDATE_INSTITUTION_SETTINGS => 'update:institution-settings',
            self::DELETE_INSTITUTION_SETTINGS => 'delete:institution-settings',
            self::RESTORE_INSTITUTION_SETTINGS => 'restore:institution-settings',
            self::FORCE_DELETE_INSTITUTION_SETTINGS => 'forceDelete:institution-settings',
            self::IMPORT_INSTITUTION_SETTINGS => 'import:institution-settings',
            self::EXPORT_INSTITUTION_SETTINGS => 'export:institution-settings',
            self::VIEW_INSTITUTION_SETTINGS_AUDIT_TRAIL => 'viewAuditTrail:institution-settings',
            /** INSTITUTION DEPARTMENTS */
            self::VIEW_ANY_DEPARTMENT_METADATA => 'viewAny:department-metadata',
            self::VIEW_DEPARTMENT_METADATA => 'view:department-metadata',
            self::CREATE_DEPARTMENT_METADATA => 'create:department-metadata',
            self::UPDATE_DEPARTMENT_METADATA => 'update:department-metadata',
            self::DELETE_DEPARTMENT_METADATA => 'delete:department-metadata',
            self::RESTORE_DEPARTMENT_METADATA => 'restore:department-metadata',
            self::FORCE_DELETE_DEPARTMENT_METADATA => 'forceDelete:department-metadata',
            self::IMPORT_DEPARTMENT_METADATA => 'import:department-metadata',
            self::EXPORT_DEPARTMENT_METADATA => 'export:department-metadata',
            self::VIEW_DEPARTMENT_METADATA_AUDIT_TRAIL => 'viewAuditTrail:department-metadata',
            self::SETUP_DEPARTMENT_LEVELS => 'department-setup:levels',
            self::SETUP_DEPARTMENT_COURSES => 'department-setup:courses',

            self::SETUP_DEPARTMENT_CLASS_SIZES => 'department-setup:class-sizes',
            self::SETUP_DEPARTMENT_WORKFLOWS => 'department-setup:workflows',
            self::VIEW_ONLY_OWN_DEPARTMENT => 'viewOnlyOwnDepartment:departments',
            /** ROOT */
            self::ROOT_MANAGE => 'root:manage',
            self::MANAGE_OWN_TENANT_DATA => 'manageOwnData:tenants',
            /** SHARED */
            self::VIEW_ANY_BANK_DETAILS => 'viewAny:bank-details',
            self::VIEW_BANK_DETAILS => 'view:bank-details',
            self::CREATE_BANK_DETAILS => 'create:bank-details',
            self::UPDATE_BANK_DETAILS => 'update:bank-details',
            self::DELETE_BANK_DETAILS => 'delete:bank-details',
            self::RESTORE_BANK_DETAILS => 'restore:bank-details',
            self::FORCE_DELETE_BANK_DETAILS => 'forceDelete:bank-details',
            self::IMPORT_BANK_DETAILS => 'import:bank-details',
            self::EXPORT_BANK_DETAILS => 'export:bank-details',
            self::VIEW_ANY_ADDRESSES => 'viewAny:addresses',
            self::VIEW_ADDRESSES => 'view:addresses',
            self::CREATE_ADDRESSES => 'create:addresses',
            self::UPDATE_ADDRESSES => 'update:addresses',
            self::DELETE_ADDRESSES => 'delete:addresses',
            self::RESTORE_ADDRESSES => 'restore:addresses',
            self::FORCE_DELETE_ADDRESSES => 'forceDelete:addresses',
            self::IMPORT_ADDRESSES => 'import:addresses',
            self::EXPORT_ADDRESSES => 'export:addresses',
            self::VIEW_ANY_CONTACTS => 'viewAny:contacts',
            self::VIEW_CONTACTS => 'view:contacts',
            self::CREATE_CONTACTS => 'create:contacts',
            self::UPDATE_CONTACTS => 'update:contacts',
            self::DELETE_CONTACTS => 'delete:contacts',
            self::RESTORE_CONTACTS => 'restore:contacts',
            self::FORCE_DELETE_CONTACTS => 'forceDelete:contacts',
            self::IMPORT_CONTACTS => 'import:contacts',
            self::EXPORT_CONTACTS => 'export:contacts',
            self::VIEW_ANY_NEXT_OF_KINS => 'viewAny:next-of-kins',
            self::VIEW_NEXT_OF_KINS => 'view:next-of-kins',
            self::CREATE_NEXT_OF_KINS => 'create:next-of-kins',
            self::UPDATE_NEXT_OF_KINS => 'update:next-of-kins',
            self::DELETE_NEXT_OF_KINS => 'delete:next-of-kins',
            self::RESTORE_NEXT_OF_KINS => 'restore:next-of-kins',
            self::FORCE_DELETE_NEXT_OF_KINS => 'forceDelete:next-of-kins',
            self::IMPORT_NEXT_OF_KINS => 'import:next-of-kins',
            self::EXPORT_NEXT_OF_KINS => 'export:next-of-kins',
            /** PORTAL */
            self::VIEW_OWN_STUDENT_DASHBOARD => 'viewOwnDashboard:students',
            self::MANAGE_OWN_STUDENT_PERSONAL_DETAILS => 'manageOwnStudentPersonalDetails:students',
            self::MANAGE_OWN_STUDENT_PROGRAM_DETAILS => 'manageOwnStudentProgramDetails:students',
            self::MANAGE_OWN_STUDENT_SPONSOR_DETAILS => 'manageOwnStudentSponsorDetails:students',
            self::MANAGE_OWN_STUDENT_CONTACT_DETAILS => 'manageOwnStudentContactDetails:students',
            self::MANAGE_OWN_STUDENT_FINANCIAL_DETAILS => 'manageOwnStudentFinancialDetails:students',
            self::MANAGE_OWN_STUDENT_ACADEMIC_DETAILS => 'manageOwnStudentAcademicDetails:students',
            /** ADMIN STUDENTS */
            self::MANAGE_STUDENTS_METADATA => 'manageStudentMetadata:admin',
            /** STUDENT_PROGRAMS*/
            self::VIEW_ANY_STUDENT_PROGRAMS => 'viewAny:student-programs',
            self::VIEW_STUDENT_PROGRAMS => 'view:student-programs',
            self::CREATE_STUDENT_PROGRAMS => 'create:student-programs',
            self::UPDATE_STUDENT_PROGRAMS => 'update:student-programs',
            self::DELETE_STUDENT_PROGRAMS => 'delete:student-programs',
            self::RESTORE_STUDENT_PROGRAMS => 'restore:student-programs',
            self::FORCE_DELETE_STUDENT_PROGRAMS => 'forceDelete:student-programs',
            self::IMPORT_STUDENT_PROGRAMS => 'import:student-programs',
            self::EXPORT_STUDENT_PROGRAMS => 'export:student-programs',
            self::CRUD_STUDENT_PROGRAMS_SETTINGS => 'crud-settings:student-programs',
            self::VIEW_STUDENT_PROGRAMS_AUDIT_TRAIL => 'viewAuditTrail:student-programs',
            self::VERIFY_CLASS_LIST => 'verify:class-list',
            self::CREATE_CLASS_LIST => 'create:class-lists',
            self::UPDATE_CLASS_LIST => 'update:class-lists',
            self::DELETE_CLASS_LIST => 'delete:class-lists',
            self::VIEW_CLASS_LIST => 'view:class-lists',
            /** STUDENTS */
            self::VIEW_ANY_STUDENTS => 'viewAny:students',
            self::VIEW_STUDENTS => 'view:students',
            self::CREATE_STUDENTS => 'create:students',
            self::UPDATE_STUDENTS => 'update:students',
            self::DELETE_STUDENTS => 'delete:students',
            self::RESTORE_STUDENTS => 'restore:students',
            self::FORCE_DELETE_STUDENTS => 'forceDelete:students',
            self::IMPORT_STUDENTS => 'import:students',
            self::EXPORT_STUDENTS => 'export:students',
            self::CRUD_STUDENTS_SETTINGS => 'crud-settings:students',
            self::VIEW_STUDENTS_AUDIT_TRAIL => 'viewAuditTrail:students',
            /** EXAMINATIONS */
            self::VIEW_ANY_EXAMINATION => 'viewAny:examinations',
            self::VIEW_EXAMINATION => 'view:examinations',
            self::CREATE_EXAMINATION => 'create:examinations',
            self::UPDATE_EXAMINATION => 'update:examinations',
            self::DELETE_EXAMINATION => 'delete:examinations',
            self::RESTORE_EXAMINATION => 'restore:examinations',
            self::FORCE_DELETE_EXAMINATION => 'forceDelete:examinations',
            self::IMPORT_EXAMINATION => 'import:examinations',
            self::EXPORT_EXAMINATION => 'export:examinations',
            self::CRUD_EXAMINATION_SETTINGS => 'crud-settings:examinations',
            self::VIEW_EXAMINATION_AUDIT_TRAIL => 'viewAuditTrail:examinations',
            /** ACCOMMODATIONS */
            self::VIEW_ANY_ACCOMMODATIONS => 'viewAny:accommodations',
            self::VIEW_ACCOMMODATIONS => 'view:accommodations',
            self::CREATE_ACCOMMODATIONS => 'create:accommodations',
            self::UPDATE_ACCOMMODATIONS => 'update:accommodations',
            self::DELETE_ACCOMMODATIONS => 'delete:accommodations',
            self::RESTORE_ACCOMMODATIONS => 'restore:accommodations',
            self::FORCE_DELETE_ACCOMMODATIONS => 'forceDelete:accommodations',
            self::IMPORT_ACCOMMODATIONS => 'import:accommodations',
            self::EXPORT_ACCOMMODATIONS => 'export:accommodations',
            self::CRUD_ACCOMMODATIONS_SETTINGS => 'crud-settings:accommodations',
            self::VIEW_ACCOMMODATIONS_AUDIT_TRAIL => 'viewAuditTrail:accommodations',
            /** DOCUMENT TEMPLATES */
            self::VIEW_ANY_DOCUMENT_TEMPLATES => 'viewAny:document-templates',
            self::VIEW_DOCUMENT_TEMPLATES => 'view:document-templates',
            self::CREATE_DOCUMENT_TEMPLATES => 'create:document-templates',
            self::UPDATE_DOCUMENT_TEMPLATES => 'update:document-templates',
            self::DELETE_DOCUMENT_TEMPLATES => 'delete:document-templates',
            self::RESTORE_DOCUMENT_TEMPLATES => 'restore:document-templates',
            self::FORCE_DELETE_DOCUMENT_TEMPLATES => 'forceDelete:document-templates',
            self::IMPORT_DOCUMENT_TEMPLATES => 'import:document-templates',
            self::EXPORT_DOCUMENT_TEMPLATES => 'export:document-templates',
            self::CRUD_DOCUMENTS_SETTING_TEMPLATES => 'crud-settings:document-templates',
            self::VIEW_DOCUMENT_TEMPLATES_AUDIT_TRAIL => 'viewAuditTrail:document-templates',
            /** FEE STRUCTURE */
            self::VIEW_ANY_FEE_STRUCTURES => 'viewAny:fee-structures',
            self::VIEW_FEE_STRUCTURES => 'view:fee-structures',
            self::CREATE_FEE_STRUCTURES => 'create:fee-structures',
            self::UPDATE_FEE_STRUCTURES => 'update:fee-structures',
            self::DELETE_FEE_STRUCTURES => 'delete:fee-structures',
            self::RESTORE_FEE_STRUCTURES => 'restore:fee-structures',
            self::FORCE_DELETE_FEE_STRUCTURES => 'forceDelete:fee-structures',
            self::IMPORT_FEE_STRUCTURES => 'import:fee-structures',
            self::EXPORT_FEE_STRUCTURES => 'export:fee-structures',
            self::CRUD_FEE_STRUCTURES => 'crud-settings:fee-structures',
            self::VIEW_FEE_STRUCTURES_AUDIT_TRAIL => 'viewAuditTrail:fee-structures',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}

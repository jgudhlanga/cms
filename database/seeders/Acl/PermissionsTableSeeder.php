<?php

namespace Database\Seeders\Acl;

use App\Enums\Shared\PermissionEnum;
use App\Models\Acl\Module;
use App\Models\Acl\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'acl' => [
                ['name' => PermissionEnum::VIEW_ACL_INDEX->value],
                /*** Acl Modules */
                ['name' => PermissionEnum::VIEW_ANY_MODULES->value],
                ['name' => PermissionEnum::VIEW_MODULE->value],
                ['name' => PermissionEnum::CREATE_MODULE->value],
                ['name' => PermissionEnum::UPDATE_MODULE->value],
                ['name' => PermissionEnum::DELETE_MODULE->value],
                ['name' => PermissionEnum::RESTORE_MODULE->value],
                ['name' => PermissionEnum::FORCE_DELETE_MODULE->value],
                ['name' => PermissionEnum::IMPORT_MODULES->value],
                ['name' => PermissionEnum::EXPORT_MODULES->value],
                ['name' => PermissionEnum::VIEW_MODULES_AUDIT_TRAIL->value],
                /*** Acl Roles */
                ['name' => PermissionEnum::VIEW_ANY_ROLES->value],
                ['name' => PermissionEnum::VIEW_ROLE->value],
                ['name' => PermissionEnum::CREATE_ROLE->value],
                ['name' => PermissionEnum::UPDATE_ROLE->value],
                ['name' => PermissionEnum::DELETE_ROLE->value],
                ['name' => PermissionEnum::RESTORE_ROLE->value],
                ['name' => PermissionEnum::FORCE_DELETE_ROLE->value],
                ['name' => PermissionEnum::IMPORT_ROLES->value],
                ['name' => PermissionEnum::EXPORT_ROLES->value],
                ['name' => PermissionEnum::VIEW_ROLES_AUDIT_TRAIL->value],

                /*** Acl Permissions */
                ['name' => PermissionEnum::VIEW_ANY_PERMISSIONS->value],
                ['name' => PermissionEnum::VIEW_PERMISSION->value],
                ['name' => PermissionEnum::CREATE_PERMISSION->value],
                ['name' => PermissionEnum::UPDATE_PERMISSION->value],
                ['name' => PermissionEnum::DELETE_PERMISSION->value],
                ['name' => PermissionEnum::RESTORE_PERMISSION->value],
                ['name' => PermissionEnum::FORCE_DELETE_PERMISSION->value],
                ['name' => PermissionEnum::IMPORT_PERMISSIONS->value],
                ['name' => PermissionEnum::EXPORT_PERMISSIONS->value],
                ['name' => PermissionEnum::VIEW_PERMISSIONS_AUDIT_TRAIL->value],
            ],
            'communications' => [
                ['name' => PermissionEnum::VIEW_ANY_COMMUNICATIONS->value],
                ['name' => PermissionEnum::VIEW_COMMUNICATION->value],
                ['name' => PermissionEnum::CREATE_COMMUNICATION->value],
                ['name' => PermissionEnum::UPDATE_COMMUNICATION->value],
                ['name' => PermissionEnum::DELETE_COMMUNICATION->value],
                ['name' => PermissionEnum::RESTORE_COMMUNICATION->value],
                ['name' => PermissionEnum::FORCE_DELETE_COMMUNICATION->value],
                ['name' => PermissionEnum::IMPORT_COMMUNICATIONS->value],
                ['name' => PermissionEnum::EXPORT_COMMUNICATIONS->value],
                ['name' => PermissionEnum::CRUD_COMMUNICATION_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_COMMUNICATION_AUDIT_TRAIL->value],
            ],
            'dashboards' => [
                ['name' => PermissionEnum::VIEW_ANY_DASHBOARD->value],
                ['name' => PermissionEnum::VIEW_DASHBOARD->value],
            ],
            'reports' => [
                ['name' => PermissionEnum::VIEW_ANY_REPORTS->value],
                ['name' => PermissionEnum::VIEW_REPORT->value],
                ['name' => PermissionEnum::CREATE_REPORT->value],
                ['name' => PermissionEnum::UPDATE_REPORT->value],
                ['name' => PermissionEnum::DELETE_REPORT->value],
                ['name' => PermissionEnum::RESTORE_REPORT->value],
                ['name' => PermissionEnum::FORCE_DELETE_REPORT->value],
                ['name' => PermissionEnum::IMPORT_REPORTS->value],
                ['name' => PermissionEnum::EXPORT_REPORTS->value],
            ],
            'tenants' => [
                ['name' => PermissionEnum::VIEW_ANY_TENANTS->value],
                ['name' => PermissionEnum::VIEW_TENANT->value],
                ['name' => PermissionEnum::CREATE_TENANT->value],
                ['name' => PermissionEnum::UPDATE_TENANT->value],
                ['name' => PermissionEnum::DELETE_TENANT->value],
                ['name' => PermissionEnum::RESTORE_TENANT->value],
                ['name' => PermissionEnum::FORCE_DELETE_TENANT->value],
                ['name' => PermissionEnum::IMPORT_TENANTS->value],
                ['name' => PermissionEnum::EXPORT_TENANTS->value],
                ['name' => PermissionEnum::CRUD_TENANTS_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_TENANTS_AUDIT_TRAIL->value],
                ['name' => PermissionEnum::MANAGE_OWN_TENANT_DATA->value],
            ],
            'users' => [
                ['name' => PermissionEnum::VIEW_ANY_USERS->value],
                ['name' => PermissionEnum::VIEW_USERS->value],
                ['name' => PermissionEnum::CREATE_USERS->value],
                ['name' => PermissionEnum::UPDATE_USERS->value],
                ['name' => PermissionEnum::DELETE_USERS->value],
                ['name' => PermissionEnum::RESTORE_USERS->value],
                ['name' => PermissionEnum::FORCE_DELETE_USERS->value],
                ['name' => PermissionEnum::IMPORT_USERS->value],
                ['name' => PermissionEnum::EXPORT_USERS->value],
                ['name' => PermissionEnum::CRUD_USERS_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_USERS_AUDIT_TRAIL->value],
            ],
            'root' => [
                ['name' => PermissionEnum::ROOT_MANAGE->value],
            ],
            'settings' => [
                ['name' => PermissionEnum::VIEW_SETTINGS->value],
                ['name' => PermissionEnum::CREATE_SETTINGS->value],
                ['name' => PermissionEnum::UPDATE_SETTINGS->value],
                ['name' => PermissionEnum::DELETE_SETTINGS->value],
                ['name' => PermissionEnum::RESTORE_SETTINGS->value],
                ['name' => PermissionEnum::FORCE_DELETE_SETTINGS->value],
                ['name' => PermissionEnum::IMPORT_SETTINGS->value],
                ['name' => PermissionEnum::EXPORT_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_SETTINGS_AUDIT_TRAIL->value],
                ['name' => PermissionEnum::VIEW_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::CREATE_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::UPDATE_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::DELETE_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::RESTORE_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::FORCE_DELETE_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::IMPORT_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::EXPORT_INSTITUTION_SETTINGS->value],
                ['name' => PermissionEnum::VIEW_INSTITUTION_SETTINGS_AUDIT_TRAIL->value],
            ],
            'institution' => [
                ['name' => PermissionEnum::VIEW_ANY_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::VIEW_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::CREATE_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::UPDATE_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::DELETE_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::RESTORE_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::FORCE_DELETE_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::IMPORT_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::EXPORT_DEPARTMENT_METADATA->value],
                ['name' => PermissionEnum::VIEW_DEPARTMENT_METADATA_AUDIT_TRAIL->value],
            ],
            'shared' => [
                ['name' => PermissionEnum::VIEW_ANY_BANK_DETAILS->value],
                ['name' => PermissionEnum::VIEW_BANK_DETAILS->value],
                ['name' => PermissionEnum::CREATE_BANK_DETAILS->value],
                ['name' => PermissionEnum::UPDATE_BANK_DETAILS->value],
                ['name' => PermissionEnum::DELETE_BANK_DETAILS->value],
                ['name' => PermissionEnum::RESTORE_BANK_DETAILS->value],
                ['name' => PermissionEnum::FORCE_DELETE_BANK_DETAILS->value],
                ['name' => PermissionEnum::IMPORT_BANK_DETAILS->value],
                ['name' => PermissionEnum::EXPORT_BANK_DETAILS->value],
                ['name' => PermissionEnum::VIEW_ANY_ADDRESSES->value],
                ['name' => PermissionEnum::VIEW_ADDRESSES->value],
                ['name' => PermissionEnum::CREATE_ADDRESSES->value],
                ['name' => PermissionEnum::UPDATE_ADDRESSES->value],
                ['name' => PermissionEnum::DELETE_ADDRESSES->value],
                ['name' => PermissionEnum::RESTORE_ADDRESSES->value],
                ['name' => PermissionEnum::FORCE_DELETE_ADDRESSES->value],
                ['name' => PermissionEnum::IMPORT_ADDRESSES->value],
                ['name' => PermissionEnum::EXPORT_ADDRESSES->value],
                ['name' => PermissionEnum::VIEW_ANY_CONTACTS->value],
                ['name' => PermissionEnum::VIEW_CONTACTS->value],
                ['name' => PermissionEnum::CREATE_CONTACTS->value],
                ['name' => PermissionEnum::UPDATE_CONTACTS->value],
                ['name' => PermissionEnum::DELETE_CONTACTS->value],
                ['name' => PermissionEnum::RESTORE_CONTACTS->value],
                ['name' => PermissionEnum::FORCE_DELETE_CONTACTS->value],
                ['name' => PermissionEnum::IMPORT_CONTACTS->value],
                ['name' => PermissionEnum::EXPORT_CONTACTS->value],
                ['name' => PermissionEnum::VIEW_ANY_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::VIEW_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::CREATE_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::UPDATE_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::DELETE_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::RESTORE_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::FORCE_DELETE_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::IMPORT_NEXT_OF_KINS->value],
                ['name' => PermissionEnum::EXPORT_NEXT_OF_KINS->value],
            ],
            'students' => [
                ['name' => PermissionEnum::VIEW_OWN_STUDENT_DASHBOARD->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_PERSONAL_DETAILS->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_PROGRAM_DETAILS->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_SPONSOR_DETAILS->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_FINANCIAL_DETAILS->value],
                ['name' => PermissionEnum::MANAGE_OWN_STUDENT_ACADEMIC_DETAILS->value],
            ],
        ];
        foreach ($permissions as $key => $rows) {
            $module = Module::where('title', $key)->first();
            foreach ($rows as $row) {
                $exist = Permission::where('name', $row['name'])->first();
                if (!$exist instanceof Permission) {
                    $row['module_id'] = $module?->id;
                    Permission::create($row);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders\Acl;

use App\Models\Acl\Module;
use App\Models\Acl\Permission;
use Illuminate\Database\Seeder;
use App\Enums\PermissionEnum;

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
			'billing' => [
				['name' => PermissionEnum::VIEW_ANY_BILLING->value],
				['name' => PermissionEnum::VIEW_BILLING->value],
				['name' => PermissionEnum::CREATE_BILLING->value],
				['name' => PermissionEnum::UPDATE_BILLING->value],
				['name' => PermissionEnum::DELETE_BILLING->value],
				['name' => PermissionEnum::RESTORE_BILLING->value],
				['name' => PermissionEnum::FORCE_DELETE_BILLING->value],
				['name' => PermissionEnum::IMPORT_BILLING->value],
				['name' => PermissionEnum::EXPORT_BILLING->value],
				['name' => PermissionEnum::CRUD_BILLING_SETTINGS->value],
				['name' => PermissionEnum::VIEW_BILLING_AUDIT_TRAIL->value],
			],
			'claims' => [
				['name' => PermissionEnum::VIEW_ANY_CLAIMS->value],
				['name' => PermissionEnum::VIEW_CLAIM->value],
				['name' => PermissionEnum::CREATE_CLAIM->value],
				['name' => PermissionEnum::UPDATE_CLAIM->value],
				['name' => PermissionEnum::DELETE_CLAIM->value],
				['name' => PermissionEnum::RESTORE_CLAIM->value],
				['name' => PermissionEnum::FORCE_DELETE_CLAIM->value],
				['name' => PermissionEnum::IMPORT_CLAIMS->value],
				['name' => PermissionEnum::EXPORT_CLAIMS->value],
				['name' => PermissionEnum::CRUD_CLAIMS_SETTINGS->value],
				['name' => PermissionEnum::VIEW_CLAIMS_AUDIT_TRAIL->value],
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
			'insurers' => [
				['name' => PermissionEnum::VIEW_ANY_INSURERS->value],
				['name' => PermissionEnum::VIEW_INSURERS->value],
				['name' => PermissionEnum::CREATE_INSURERS->value],
				['name' => PermissionEnum::UPDATE_INSURERS->value],
				['name' => PermissionEnum::DELETE_INSURERS->value],
				['name' => PermissionEnum::RESTORE_INSURERS->value],
				['name' => PermissionEnum::FORCE_DELETE_INSURERS->value],
				['name' => PermissionEnum::IMPORT_INSURERS->value],
				['name' => PermissionEnum::EXPORT_INSURERS->value],
				['name' => PermissionEnum::VIEW_INSURERS_AUDIT_TRAIL->value],
			],
			'products' => [
				['name' => PermissionEnum::VIEW_ANY_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::VIEW_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::CREATE_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::UPDATE_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::DELETE_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::RESTORE_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::FORCE_DELETE_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::IMPORT_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::EXPORT_PRODUCT_SETTINGS->value],
				['name' => PermissionEnum::VIEW_PRODUCT_SETTINGS_AUDIT_TRAIL->value],
				# Premiums
				['name' => PermissionEnum::VIEW_ANY_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::VIEW_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::CREATE_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::UPDATE_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::DELETE_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::RESTORE_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::FORCE_DELETE_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::IMPORT_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::EXPORT_PREMIUM_SETTINGS->value],
				['name' => PermissionEnum::VIEW_PREMIUM_SETTINGS_AUDIT_TRAIL->value],
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
				['name' => PermissionEnum::VIEW_USER->value],
				['name' => PermissionEnum::CREATE_USER->value],
				['name' => PermissionEnum::UPDATE_USER->value],
				['name' => PermissionEnum::DELETE_USER->value],
				['name' => PermissionEnum::RESTORE_USER->value],
				['name' => PermissionEnum::FORCE_DELETE_USER->value],
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
			],
			'portfolios' => [
				['name' => PermissionEnum::VIEW_ANY_PORTFOLIOS->value],
				['name' => PermissionEnum::VIEW_PORTFOLIOS->value],
				['name' => PermissionEnum::CREATE_PORTFOLIOS->value],
				['name' => PermissionEnum::UPDATE_PORTFOLIOS->value],
				['name' => PermissionEnum::DELETE_PORTFOLIOS->value],
				['name' => PermissionEnum::RESTORE_PORTFOLIOS->value],
				['name' => PermissionEnum::FORCE_DELETE_PORTFOLIOS->value],
				['name' => PermissionEnum::IMPORT_PORTFOLIOS->value],
				['name' => PermissionEnum::EXPORT_PORTFOLIOS->value],
				['name' => PermissionEnum::CRUD_PORTFOLIOS_SETTINGS->value],
				['name' => PermissionEnum::VIEW_PORTFOLIOS_AUDIT_TRAIL->value],
			],
			'schemes' => [
				['name' => PermissionEnum::VIEW_ANY_SCHEMES->value],
				['name' => PermissionEnum::VIEW_SCHEMES->value],
				['name' => PermissionEnum::CREATE_SCHEMES->value],
				['name' => PermissionEnum::UPDATE_SCHEMES->value],
				['name' => PermissionEnum::DELETE_SCHEMES->value],
				['name' => PermissionEnum::RESTORE_SCHEMES->value],
				['name' => PermissionEnum::FORCE_DELETE_SCHEMES->value],
				['name' => PermissionEnum::IMPORT_SCHEMES->value],
				['name' => PermissionEnum::EXPORT_SCHEMES->value],
				['name' => PermissionEnum::VIEW_SCHEMES_AUDIT_TRAIL->value],
				['name' => PermissionEnum::VIEW_ANY_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::VIEW_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::CREATE_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::UPDATE_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::DELETE_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::RESTORE_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::FORCE_DELETE_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::IMPORT_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::EXPORT_SCHEME_BRANCHES->value],
				['name' => PermissionEnum::VIEW_SCHEME_BRANCHES_AUDIT_TRAIL->value],
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

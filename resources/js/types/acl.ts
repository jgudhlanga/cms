export type Module = {
	type?: string,
	id?: string,
	attributes: {
		title: string,
		description?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
}
export type ModuleParams = {
	title: string,
	description?: string,
}
export type PermissionParams = {
	name: string,
	description?: string,
}

export type RoleParams = {
	name: string,
	description?: string,
	permissions: (string | number)[] | null | undefined,
}
export type Role = {
	type?: string,
	id?: string,
	attributes: {
		name: string,
		guardName?: string,
		description?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
		permissionsCount?: string,
		usersCount?: string,
	},
	relationships?: {
		permissions?: Array<Permission>
	},
}
export type Permission = {
	type?: string,
	id?: string,
	attributes: {
		name: string,
		guardName?: string,
		description?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
	relationships?: {
		module?: Module
	},
}

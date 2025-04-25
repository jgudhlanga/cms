import { Component } from 'vue';

export interface TenantInterface {
	type: string,
	id: string,
	attributes: {
		name: string,
		logo?: any,
		bio?: any,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	}
}

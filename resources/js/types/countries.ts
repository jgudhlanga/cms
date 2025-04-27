export type Country = {
	type?: string,
	id?: string,
	attributes: {
		name: string,
		flag?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
}
export type CountryParams = {
	name: string,
	flag?: string,
}

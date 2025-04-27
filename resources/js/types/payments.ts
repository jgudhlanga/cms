export type PaymentDay = {
	type?: string,
	id?: string,
	attributes: {
		title: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},

}
export type PaymentDayParams = {
	title: string,
}

export type PaymentFrequency = {
	type?: string,
	id?: string,
	attributes: {
		title: string,
		deletedAt?: string,
		createdAt?: string,
		updatedAt?: string,
	},

}
export type PaymentFrequencyParams = {
	title: string,
}

export type PaymentMethod = {
	type?: string,
	id?: string,
	attributes: {
		title: string,
		deletedAt?: string,
		createdAt?: string,
		updatedAt?: string,
	},

}
export type PaymentMethodParams = {
	title: string,
}

import { SelectOption } from '@/types/utils';

export type Address = {
	type?: string,
	id?: string,
	attributes: {
		address1?: string,
		address2?: string,
		address3?: string,
		address4?: string,
		address5?: string,
		address6?: string,
		addressIsMain?: boolean,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
}

export type BankDetail = {
	type?: string,
	id?: string,
	attributes: {
		bankId?: string,
		bank?: string,
		bankBranchId?: string,
		bankBranch?: string,
		bankBranchCode?: string,
		bankAccountTypeId?: string,
		bankAccountType?: string,
		bankAccountHolder?: string,
		bankAccountNumber?: string,
		bankAccountNumberDecrypted?: string,
		bankAccountIsMain?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
}

export type Contact = {
	type?: string,
	id?: string,
	attributes: {
		name?: string,
		phoneNumber?: string,
		altPhoneNumber?: string,
		emailAddress?: string,
		altEmailAddress?: string,
		contactIsMain?: string,
		createdAt?: string,
		updatedAt?: string,
		deletedAt?: string,
	},
}

export type AddressParams = {
	address_1: string,
	address_2: string,
	address_3: string,
	address_4: string,
	address_5: string,
	address_6: string,
	address_is_main: boolean
}

export type BankDetailParams = {
	bank_id: string | number,
	bank_branch_id: string | number,
	bank_account_type_id: string | number,
	bank_account_holder: string,
	bank_account_number: string,
	bank_account_is_main: boolean;
	bank: SelectOption | null;
	bankAccountType: SelectOption | null;
	bankBranch: SelectOption | null;
	bankBranchCode: string;
}

export type ContactParams = {
	name: string,
	phone_number: string,
	alt_phone_number: string,
	email_address: string,
	alt_email_address: string,
	contact_is_main: boolean,
}

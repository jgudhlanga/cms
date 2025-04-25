import { trans, trans_choice } from 'laravel-vue-i18n';
import { z } from 'zod';
import {SelectOption} from '@/types/utils';

export const useSharedFormSchema = () => {
	const titleSchema = () =>
		z.object({
			title: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.title', 1) }))
		});
	const nameSchema = () =>
		z.object({
			name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.name', 1) }))
		});
	const codeSchema = () =>
		z.object({
			code: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.code', 1) }))
		});
	const registeredNameSchema = () =>
		z.object({
			registered_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.registered_name', 1) }))
		});
	const tradingNameSchema = () =>
		z.object({
			trading_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.trading_name') }))
		});
	const registrationNumberSchema = () =>
		z.object({
			registration_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.registration_number', 1) }))
		});
	const passwordSchema = () =>
		z.object({
			password: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.password') }))
		});
	const contactNameSchema = () =>
		z.object({
			contact_name: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.representative_name') }))
		});
	const emailAddressSchema = () =>
		z.object({
			email_address: z
				.string()
				.nonempty(trans('trans.enter_required_field', { field: trans('trans.email_address') }))
				.email()
		});
	const phoneNumberSchema = () =>
		z.object({
			phone_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.phone_number') }))
		});
	const addressOneSchema = () =>
		z.object({
			address_1: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.street_number') }))
		});
	const addressTwoSchema = () =>
		z.object({
			address_2: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.street_name') }))
		});
	const addressThreeSchema = () =>
		z.object({
			address_3: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.city_town_suburb') }))
		});
	const schemeNumberSchema = () =>
		z.object({
			scheme_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.scheme_number', 1) }))
		});
	const principalMemberNumberSchema = () =>
		z.object({
			number_of_principal_members: z.number({
				invalid_type_error: trans('trans.enter_required_field', { field: trans('trans.number_of_principal_members') })
			})
		});
	const membersAboveYearsLimitSchema = () =>
		z.object({
			members_above_years_limit: z.number({
				invalid_type_error: trans('trans.enter_required_field', { field: trans('trans.members_above_years_limit') })
			})
		});
	const bankAccountHolderSchema = () =>
		z.object({
			bank_account_holder: z.string().nonempty(trans('trans.enter_required_field', { field: trans('trans.account_holder_name') }))
		});
	const bankAccountNumberSchema = () =>
		z.object({
			bank_account_number: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.account_number', 1) }))
		});
	/* Dropdown Schema */
	const tradingStatusSchema = () =>
		z.object({
			tradingStatus: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.trading_status', 1) }) }
			)
		});
	const provinceSchema = () =>
		z.object({
			province: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.province', 1) }) }
			)
		});

	function validateSelectOption(val: SelectOption) {
		return val !== null && val?.value !== '';
	}

	const bankSchema = () =>
		z.object({
			bank: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.bank', 1) }) }
			)
		});
	const bankBranchSchema = () =>
		z.object({
			bankBranch: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.branch', 1) }) }
			)
		});
	const bankAccountTypeSchema = () =>
		z.object({
			bankAccountType: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.account_type', 1) }) }
			)
		});
	const memberTypeSchema = () =>
		z.object({
			memberType: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.member_type', 1) }) }
			)
		});
	const packageSchema = () =>
		z.object({
			package: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.package', 1) }) }
			)
		});
	const productSchema = () =>
		z.object({
			product: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.product', 1) }) }
			)
		});
	const productTypeSchema = () =>
		z.object({
			productType: z.any().refine(
				(val) => validateSelectOption(val),
				{ message: trans('trans.select_valid_field', { field: trans_choice('trans.product_type', 1) }) }
			)
		});
	return {
		addressOneSchema,
		addressThreeSchema,
		addressTwoSchema,
		bankAccountHolderSchema,
		bankAccountNumberSchema,
		bankAccountTypeSchema,
		bankBranchSchema,
		bankSchema,
		codeSchema,
		contactNameSchema,
		emailAddressSchema,
		memberTypeSchema,
		membersAboveYearsLimitSchema,
		nameSchema,
		packageSchema,
		passwordSchema,
		phoneNumberSchema,
		principalMemberNumberSchema,
		productSchema,
		productTypeSchema,
		provinceSchema,
		registeredNameSchema,
		registrationNumberSchema,
		schemeNumberSchema,
		titleSchema,
		tradingNameSchema,
		tradingStatusSchema
	};
};

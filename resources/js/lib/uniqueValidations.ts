import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import HttpService from '@/services/http.service';
import { trans } from 'laravel-vue-i18n';
import { z } from 'zod';

export const emailUniqueSchema = (url: string) =>
    z.object({
        email: z
            .string()
            .nonempty(trans('trans.enter_required_field', { field: trans('trans.email_address') }))
            .email(trans('trans.enter_valid_email_address'))
            .refine(
                async (email) => {
                    if (!email || email.trim() === '') {
                        return true;
                    }
                    const response = await HttpService.get(`${url}${email}`);
                    return response.available;
                },
                {
                    message: trans('trans.email_already_taken'),
                },
            ),
    });
export const phoneNumberUniqueSchema = (url: string) =>
    z.object({
        phone_number: z
            .string()
            .nonempty(trans('trans.enter_required_field', { field: trans('trans.phone_number') }))
            .refine(
                async (phone_number) => {
                    if (!phone_number || phone_number.trim() === '') {
                        return true;
                    }
                    const response = await HttpService.get(`${url}${phone_number}`);
                    return response.available;
                },
                {
                    message: trans('trans.phone_number_already_taken'),
                },
            ),
    });
export const employeeNumberUniqueSchema = (url: string) =>
    z.object({
        employee_number: z
            .string()
            .nonempty(trans('trans.enter_required_field', { field: trans('trans.employee_number') }))
            .refine(
                async (employee_number) => {
                    if (!employee_number || employee_number.trim() === '') {
                        return true;
                    }
                    const response = await HttpService.get(`${url}${employee_number}`);
                    return response.available;
                },
                {
                    message: trans('trans.employee_number_already_taken'),
                },
            ),
    });

export const idNumberUniqueSchema = (url: string) =>
    z.object({
        id_number: z
            .string()
            .nonempty(trans('trans.enter_required_field', { field: trans('trans.id_number') }))
            .refine(
                (id_number) => {
                    if (!id_number || id_number.trim() === '') {
                        return true;
                    }
                    return isValidZimbabweanIdNumber(id_number);
                },
                {
                    message: trans('trans.enrollment_invalid_national_id'),
                },
            )
            .refine(
                async (id_number) => {
                    if (!id_number || id_number.trim() === '') {
                        return true;
                    }
                    const response = await HttpService.get(`${url}${id_number}`);
                    return response.available;
                },
                {
                    message: trans('trans.id_number_already_taken'),
                },
            ),
    });

export const passportNumberUniqueSchema = (url: string) =>
    z.object({
        passport_number: z
            .string()
            .nonempty(trans('trans.enter_required_field', { field: trans('trans.passport_number') }))
            .refine(
                async (passport_number) => {
                    if (!passport_number || passport_number.trim() === '') {
                        return true;
                    }
                    const response = await HttpService.get(`${url}${passport_number}`);
                    return response.available;
                },
                {
                    message: trans('trans.passport_number_already_taken'),
                },
            ),
    });

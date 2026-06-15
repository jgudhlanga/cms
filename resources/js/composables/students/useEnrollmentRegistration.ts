import HttpService from '@/services/http.service';

export type RegistrationPath = 'zimbabwean' | 'international' | 'returning';

export type EnrollmentLookupResult = {
    found: boolean;
    maskedName: string | null;
    maskedEmail: string | null;
    loginEmail: string | null;
};

export type ReturningLookupType = 'id_number' | 'student_number';

const guestEnrollmentBase = '/api/v1/guest/enrollment';

export function useEnrollmentRegistration() {
    const checkNationalId = async (idNumber: string): Promise<EnrollmentLookupResult> => {
        return HttpService.post(`${guestEnrollmentBase}/check-national-id`, { id_number: idNumber });
    };

    const checkPassport = async (passportNumber: string): Promise<EnrollmentLookupResult> => {
        return HttpService.post(`${guestEnrollmentBase}/check-passport`, { passport_number: passportNumber });
    };

    const lookupReturning = async (type: ReturningLookupType, value: string): Promise<EnrollmentLookupResult> => {
        return HttpService.post(`${guestEnrollmentBase}/lookup`, { type, value });
    };

    return {
        checkNationalId,
        checkPassport,
        lookupReturning,
    };
}

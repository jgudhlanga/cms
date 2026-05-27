/** Matches backend App\Rules\ZimbabweanIdNumber (hyphenated format). */
export const ZIMBABWEAN_ID_REGEX = /^\d{2}-\d{5,7}[A-Za-z]\d{2}$/;

export function isValidZimbabweanIdNumber(idNumber: string): boolean {
	return ZIMBABWEAN_ID_REGEX.test(idNumber.trim());
}

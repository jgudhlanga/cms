export type HostelGenderType = 'male' | 'female' | 'mixed';

export function genderTitleMatchesHostelType(
    genderTitle: string | null | undefined,
    hostelType: HostelGenderType | null | undefined,
): boolean {
    if (!hostelType || hostelType === 'mixed') {
        return true;
    }

    const title = (genderTitle ?? '').toLowerCase().trim();

    if (title === '') {
        return false;
    }

    if (hostelType === 'female') {
        return title.includes('female') || title.includes('woman');
    }

    if (hostelType === 'male') {
        return title.includes('male') && !title.includes('female');
    }

    return true;
}

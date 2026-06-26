const FIELD_SELECTORS: Record<string, string> = {
    email: '#email_address',
    title: '#title',
    gender: '[data-field="gender"]',
    maritalStatus: '[data-field="maritalStatus"]',
    idType: '[data-field="idType"]',
    country: '[data-field="country"]',
    relationship: '[data-field="relationship"]',
    department: '[data-field="department"]',
    level: '[data-field="level"]',
    course: '[data-field="course"]',
    modeOfStudy: '[data-field="modeOfStudy"]',
    disability_status: '#disability_status',
};

export function scrollToFirstError(errors: Record<string, string | string[]>, extraKeys: string[] = []): boolean {
    const keys = [...extraKeys, ...Object.keys(errors)].filter(Boolean);
    if (keys.length === 0) {
        return false;
    }

    for (const key of keys) {
        const selector = FIELD_SELECTORS[key] ?? `#${key}`;
        const el = document.querySelector(selector) as HTMLElement | null;
        if (el) {
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
            const focusable = el.matches('input,select,textarea,button')
                ? el
                : (el.querySelector('input,select,textarea,button') as HTMLElement | null);
            focusable?.focus({ preventScroll: true });
            return true;
        }
    }

    return false;
}

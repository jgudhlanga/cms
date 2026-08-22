import { describe, expect, it } from 'vitest';

import { isTranslationKey, resolveUiLabel } from '@/lib/uiLabel';

describe('isTranslationKey', () => {
    it('detects dotted i18n keys and ignores real labels', () => {
        expect(isTranslationKey('trans.activity_user_me')).toBe(true);
        expect(isTranslationKey('trans.select_one')).toBe(true);
        expect(isTranslationKey('students.filter_all_genders')).toBe(true);
        expect(isTranslationKey('Me')).toBe(false);
        expect(isTranslationKey('Tinashe M.')).toBe(false);
        expect(isTranslationKey('user@example.com')).toBe(false);
        expect(isTranslationKey('NDIT')).toBe(false);
    });
});

describe('resolveUiLabel', () => {
    it('returns translated text when the translator resolves the key', () => {
        const translate = (key: string) => (key === 'trans.activity_user_me' ? 'Me' : key);

        expect(resolveUiLabel('trans.activity_user_me', translate)).toBe('Me');
    });

    it('uses hardcoded fallbacks when the translator is missing or returns the key', () => {
        expect(resolveUiLabel('trans.activity_user_me')).toBe('Me');
        expect(resolveUiLabel('trans.select_one', (key) => key)).toBe('Select one...');
        expect(resolveUiLabel('trans.ui_sort_department_az', (key) => key)).toBe('Department (A–Z)');
        expect(resolveUiLabel('students.filters_n_selected', undefined, { count: 4 })).toBe('4 selected');
    });

    it('title-cases the last key segment when there is no fallback', () => {
        expect(resolveUiLabel('hms.room_status_vacant')).toBe('Room Status Vacant');
    });

    it('leaves ordinary option labels unchanged', () => {
        expect(resolveUiLabel('Information Technology')).toBe('Information Technology');
        expect(resolveUiLabel('Tinashe M.')).toBe('Tinashe M.');
    });
});

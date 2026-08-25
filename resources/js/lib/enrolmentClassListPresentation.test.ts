import { describe, expect, it } from 'vitest';

import {
    applicationsExcludedFromRanking,
    filterEnrolmentApplications,
    matchesEnrolmentApplicationSearch,
    toTitleCase,
} from '@/lib/enrolmentClassListPresentation';
import type { EnrolmentApplication } from '@/types/enrolments';

const sampleApplication = (overrides: Partial<EnrolmentApplication> = {}): EnrolmentApplication =>
    ({
        applicationId: 1,
        applicationTrackingNumber: 'TN2549410625',
        applicationDate: '2025-10-12 22:25:57',
        studentId: '1',
        studentName: 'TANYA DUBE',
        studentNumber: '',
        email: 'tanya@example.com',
        phoneNumber: '0774640530',
        gender: 'female',
        disabilityStatus: null,
        workflowStep: null,
        receiptId: null,
        receiptAmount: null,
        examSittingsCount: 1,
        firstExamYear: '2020',
        inClassList: true,
        classListType: 'provisional',
        requiredLevelCompleted: true,
        readWriteAcknowledged: true,
        offerAccepted: false,
        academicResults: [],
        totalScore: 7,
        hasNoPayment: false,
        hasInvalidGrade: false,
        ...overrides,
    }) as EnrolmentApplication;

describe('toTitleCase', () => {
    it('title-cases all-caps names and status labels', () => {
        expect(toTitleCase('TANYA DUBE')).toBe('Tanya Dube');
        expect(toTitleCase('provisional Applications')).toBe('Provisional Applications');
    });

    it('handles hyphenated names', () => {
        expect(toTitleCase('MARY-JANE SMITH')).toBe('Mary-Jane Smith');
    });

    it('returns empty string for blank input', () => {
        expect(toTitleCase('')).toBe('');
        expect(toTitleCase(null)).toBe('');
    });
});

describe('matchesEnrolmentApplicationSearch', () => {
    it('matches by name, phone, tracking number, and email', () => {
        const application = sampleApplication();

        expect(matchesEnrolmentApplicationSearch(application, 'tanya')).toBe(true);
        expect(matchesEnrolmentApplicationSearch(application, '0774640530')).toBe(true);
        expect(matchesEnrolmentApplicationSearch(application, 'TN2549410625')).toBe(true);
        expect(matchesEnrolmentApplicationSearch(application, 'tanya@example.com')).toBe(true);
        expect(matchesEnrolmentApplicationSearch(application, 'missing')).toBe(false);
    });

    it('returns all rows when query is blank', () => {
        expect(matchesEnrolmentApplicationSearch(sampleApplication(), '')).toBe(true);
        expect(matchesEnrolmentApplicationSearch(sampleApplication(), '   ')).toBe(true);
    });
});

describe('filterEnrolmentApplications', () => {
    it('filters a list by search query', () => {
        const applications = [
            sampleApplication({ applicationId: 1, studentName: 'Melisah Kuona' }),
            sampleApplication({ applicationId: 2, studentName: 'Whitney Rice' }),
        ];

        expect(filterEnrolmentApplications(applications, 'whitney')).toHaveLength(1);
        expect(filterEnrolmentApplications(applications, '')).toHaveLength(2);
    });
});

describe('applicationsExcludedFromRanking', () => {
    it('returns applications dropped by ranking so they can still be listed', () => {
        const ranked = sampleApplication({ applicationId: 10, studentName: 'Ranked Applicant' });
        const unqualified = sampleApplication({ applicationId: 20, studentName: 'Missing Grades' });

        expect(applicationsExcludedFromRanking([ranked, unqualified], [ranked])).toEqual([unqualified]);
    });

    it('returns an empty list when every application is ranked', () => {
        const ranked = sampleApplication({ applicationId: 10 });

        expect(applicationsExcludedFromRanking([ranked], [ranked])).toEqual([]);
    });
});

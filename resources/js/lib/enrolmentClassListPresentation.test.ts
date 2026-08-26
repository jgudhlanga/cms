import { describe, expect, it } from 'vitest';

import {
    applicationsExcludedFromRanking,
    filterEnrolmentApplications,
    getClassListTypeFromRank,
    getClassListTypeRowClass,
    getDisabledQualifiedRowClass,
    getRankBandClassList,
    isDisabledEnrolmentGroup,
    isWithinSelectableBand,
    matchesEnrolmentApplicationSearch,
    occupiesIntakeSeat,
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

describe('selection rank bands', () => {
    const slotSize = 10;

    it('colors ranks 1..N green (provisional) and N+1..2N purple (waiting)', () => {
        expect(getRankBandClassList(0, slotSize)).toBe('bg-green-100');
        expect(getRankBandClassList(9, slotSize)).toBe('bg-green-100');
        expect(getRankBandClassList(10, slotSize)).toBe('bg-purple-100');
        expect(getRankBandClassList(19, slotSize)).toBe('bg-purple-100');
        expect(getRankBandClassList(20, slotSize)).toBe('');
    });

    it('maps ranks to provisional / waiting / empty', () => {
        expect(getClassListTypeFromRank(0, slotSize)).toBe('provisional');
        expect(getClassListTypeFromRank(9, slotSize)).toBe('provisional');
        expect(getClassListTypeFromRank(10, slotSize)).toBe('waiting');
        expect(getClassListTypeFromRank(19, slotSize)).toBe('waiting');
        expect(getClassListTypeFromRank(20, slotSize)).toBe('');
    });

    it('marks only the first 2N ranks as selectable for first-time add', () => {
        expect(isWithinSelectableBand(0, slotSize)).toBe(true);
        expect(isWithinSelectableBand(19, slotSize)).toBe(true);
        expect(isWithinSelectableBand(20, slotSize)).toBe(false);
        expect(isWithinSelectableBand(0, 0)).toBe(false);
    });
});

describe('class list type row colors', () => {
    it('keeps provisional green and waiting purple after generation', () => {
        expect(getClassListTypeRowClass('provisional')).toBe('bg-green-100');
        expect(getClassListTypeRowClass('waiting')).toBe('bg-purple-100');
        expect(getClassListTypeRowClass('failed')).toBe('bg-red-50/80');
        expect(getClassListTypeRowClass('verified')).toBe('bg-primary/10');
        expect(getClassListTypeRowClass('final')).toBe('bg-emerald-50');
        expect(getClassListTypeRowClass(null)).toBe('');
    });
});

describe('occupiesIntakeSeat', () => {
    it('counts provisional, verified, and final only', () => {
        expect(occupiesIntakeSeat('provisional')).toBe(true);
        expect(occupiesIntakeSeat('verified')).toBe(true);
        expect(occupiesIntakeSeat('final')).toBe(true);
        expect(occupiesIntakeSeat('waiting')).toBe(false);
        expect(occupiesIntakeSeat('failed')).toBe(false);
        expect(occupiesIntakeSeat(null)).toBe(false);
    });
});

describe('disabled qualified row class', () => {
    it('always uses green for qualified disabled rows', () => {
        expect(getDisabledQualifiedRowClass('provisional')).toBe('bg-green-100');
        expect(getDisabledQualifiedRowClass('waiting')).toBe('bg-green-100');
        expect(getDisabledQualifiedRowClass('verified')).toBe('bg-green-100');
        expect(getDisabledQualifiedRowClass(null)).toBe('bg-green-100');
    });

    it('uses red for failed disabled rows', () => {
        expect(getDisabledQualifiedRowClass('failed')).toBe('bg-red-50/80');
    });

    it('identifies the disabled enrolment group', () => {
        expect(isDisabledEnrolmentGroup('disabled')).toBe(true);
        expect(isDisabledEnrolmentGroup('females')).toBe(false);
    });
});

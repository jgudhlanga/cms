<?php

/**
 * Application routes reachable without an authenticated session, a valid signature or the guest-only
 * middleware. Every entry was reviewed; adding a new public route means adding it here on purpose.
 *
 * @see tests/Feature/Security/RouteAuthorizationInventoryTest.php
 */
return [
    // Public website and registration lookups (read-only, throttled by "public-lookups").
    'v1.academic-levels.index',
    'v1.academic-staff.grouped-by-department',
    'v1.address-types.index',
    'v1.countries.index',
    'v1.courses.index',
    'v1.department-course-requirements',
    'v1.department-level-courses.by-institution-department',
    'v1.department-level-courses.index',
    'v1.department-level-requirements',
    'v1.department-levels.index',
    'v1.departments.index',
    'v1.districts.index',
    'v1.document-types.index',
    'v1.employment-types.index',
    'v1.enrolments.course-modes',
    'v1.fee-types.index',
    'v1.genders.index',
    'v1.grades.index',
    'v1.id-types.index',
    'v1.institution-departments.index',
    'v1.intake-periods.index',
    'v1.levels.index',
    'v1.marital-statuses.index',
    'v1.modes-of-study.course-modes',
    'v1.modes-of-study.index',
    'v1.provinces.index',
    'v1.races.index',
    'v1.relationships.index',
    'v1.religions.index',
    'v1.semesters.index',
    'v1.sponsor-types.index',
    'v1.staff.index', // Guests get PublicStaffResource (no personal identifiers).
    'v1.student-enrolment-statuses.index',
    'v1.subjects.index',
    'v1.titles.index',
    'v1.workflow-step-actions.index',
    'v1.workflow-steps.index',

    // Middleware allows guests, but UserController::index authorizes viewAny (guests get 403).
    'v1.users.index',

    // Mobile/external app authentication (throttled by "auth-api").
    'v1.auth.forgot-password',
    'v1.auth.login',
    'v1.auth.register',

    // Registration flow and uniqueness checks (throttled).
    'v1.check',
    'v1.guest.enrollment.check-national-id',
    'v1.guest.enrollment.check-passport',
    'v1.guest.enrollment.lookup',
    'v1.guest.enrollment.programmes',
    'portal.create',
    'portal.store',
    'portal.confirmation', // Only renders for the logged-in owner.
    'portal.register.account',
    'portal.register.college',
    'portal.register.level',
    'portal.register.programme',
    'portal.register.select-college',
    'portal.register.select-level',
    'portal.register.select-programme',
    'portal.register.track',
    'portal.register.select-track',
    'portal.registration.maintenance',

    // Signed link or owner/staff access enforced in the controller.
    'documents.offer-letter',

    // Printed ID card QR verification.
    'id-cards.verify',

    // Payment gateway webhook (credentials checked in PaymentController, throttled).
    'integrations.payments.result',
];

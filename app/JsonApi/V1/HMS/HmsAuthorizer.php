<?php

namespace App\JsonApi\V1\HMS;

use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelAmenity;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelLeave;
use App\Models\HMS\HostelNotice;
use App\Models\HMS\HostelQuery;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Policies\HMS\HostelLeavePolicy;
use App\Policies\HMS\HostelQueryPolicy;
use App\Support\HMS\HmsStudentAccess;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class HmsAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return match ($modelClass) {
            HostelApplication::class => $this->authorizeStudentScopedIndex($user, HostelApplication::class),
            HostelRoomAllocation::class => $this->authorizeStudentScopedIndex($user, HostelRoomAllocation::class),
            HostelQuery::class => $this->authorizeStudentScopedIndex($user, HostelQuery::class),
            HostelLeave::class => $this->authorizeStudentScopedIndex($user, HostelLeave::class),
            HostelNotice::class => $this->authorizeStudentScopedIndex($user, HostelNotice::class),
            Hostel::class => $user->can('viewAny:hostels') || $user->can('view:hostels'),
            HostelAmenity::class => $user->can('viewAny:hostel-amenities') || $user->can('view:hostel-amenities'),
            HostelRoom::class => true,
            HmsSetting::class => $user->can('viewAny:hms-settings') || $user->can('view:hms-settings'),
            default => false,
        };
    }

    public function store(Request $request, string $modelClass): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($modelClass === HostelApplication::class) {
            $studentId = (int) data_get($request->input('data'), 'attributes.studentId', 0);
            $student = $studentId > 0 ? Student::query()->find($studentId) : null;

            if ($student !== null && HmsStudentAccess::canCreateApplicationFor($user, $student)) {
                return true;
            }

            return $user->can('create', HostelApplication::class);
        }

        if (in_array($modelClass, [HostelQuery::class, HostelLeave::class], true)) {
            $studentId = (int) data_get($request->input('data'), 'attributes.studentId', 0);
            $student = $studentId > 0 ? Student::query()->find($studentId) : null;

            if ($student !== null) {
                return match ($modelClass) {
                    HostelQuery::class => (new HostelQueryPolicy)->createForStudent($user, $student),
                    HostelLeave::class => (new HostelLeavePolicy)->createForStudent($user, $student),
                    default => false,
                };
            }

            return match ($modelClass) {
                HostelQuery::class => $user->can('create', HostelQuery::class),
                HostelLeave::class => $user->can('create', HostelLeave::class),
                default => false,
            };
        }

        return match ($modelClass) {
            HostelNotice::class => $user->can('create', HostelNotice::class),
            HostelAmenity::class => $user->can('create', HostelAmenity::class),
            HmsSetting::class => $user->can('create', HmsSetting::class),
            default => false,
        };
    }

    public function show(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return match (true) {
            $model instanceof HostelApplication => HmsStudentAccess::canViewApplication($user, $model)
                || (int) $user->tenant_id === (int) $model->tenant_id,
            $model instanceof HostelRoomAllocation => HmsStudentAccess::canViewAllocation($user, $model),
            $model instanceof HostelQuery => $user->can('view', $model),
            $model instanceof HostelLeave => $user->can('view', $model),
            $model instanceof HostelNotice => $user->can('view', $model),
            $model instanceof Hostel => $user->can('viewAny:hostels') || $user->can('view:hostels'),
            $model instanceof HostelAmenity => $user->can('viewAny:hostel-amenities') || $user->can('view:hostel-amenities'),
            $model instanceof HostelRoom => true,
            $model instanceof HmsSetting => $user->can('viewAny:hms-settings') || $user->can('view:hms-settings'),
            default => false,
        };
    }

    public function update(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($model instanceof HostelApplication && HmsStudentAccess::canManageOwnAccommodation($user)) {
            return false;
        }

        if (in_array($model::class, [HostelQuery::class, HostelLeave::class, HostelNotice::class], true)
            && HmsStudentAccess::canManageOwnAccommodation($user)
            && ! HmsStudentAccess::isStaffHmsUser($user)) {
            return false;
        }

        return match (true) {
            $model instanceof HostelApplication => $user->can('update', $model),
            $model instanceof HostelQuery => $user->can('update', $model),
            $model instanceof HostelLeave => $user->can('update', $model),
            $model instanceof HostelNotice => $user->can('update', $model),
            $model instanceof HostelAmenity => $user->can('update', $model),
            $model instanceof HmsSetting => $user->can('update', $model),
            default => false,
        };
    }

    public function destroy(Request $request, object $model): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($model instanceof HostelApplication && HmsStudentAccess::canManageOwnAccommodation($user)) {
            return false;
        }

        if (in_array($model::class, [HostelQuery::class, HostelLeave::class, HostelNotice::class], true)
            && HmsStudentAccess::canManageOwnAccommodation($user)
            && ! HmsStudentAccess::isStaffHmsUser($user)) {
            return false;
        }

        return match (true) {
            $model instanceof HostelApplication => $user->can('delete', $model),
            $model instanceof HostelQuery => $user->can('delete', $model),
            $model instanceof HostelLeave => $user->can('delete', $model),
            $model instanceof HostelNotice => $user->can('delete', $model),
            $model instanceof HostelAmenity => $user->can('delete', $model),
            $model instanceof HmsSetting => $user->can('delete', $model),
            default => false,
        };
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return false;
    }

    private function authorizeStudentScopedIndex(User $user, string $modelClass): bool
    {
        $studentId = HmsStudentAccess::studentIdFromRequest();

        if ($studentId === null) {
            return match ($modelClass) {
                HostelApplication::class => true,
                HostelRoomAllocation::class => $user->can('viewAny:hostel-room-allocations'),
                HostelQuery::class => $user->can('viewAny:hostel-queries'),
                HostelLeave::class => $user->can('viewAny:hostel-leaves'),
                HostelNotice::class => $user->can('viewAny:hostel-notices'),
                default => false,
            };
        }

        $student = Student::query()->find($studentId);

        if ($student === null) {
            return false;
        }

        return HmsStudentAccess::canViewStudentHms($user, $student);
    }
}

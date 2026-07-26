<?php

namespace App\Support\Rbac;

use App\Enums\Rbac\ScopeLevelEnum;
use App\Models\HMS\Hostel;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Users\User;

class UserAccessScope
{
    public function __construct(private readonly ?User $user = null) {}

    public static function for(?User $user = null): self
    {
        return new self($user ?? auth()->user());
    }

    public function level(): ScopeLevelEnum
    {
        $user = $this->user;

        if (! $user instanceof User) {
            return ScopeLevelEnum::College;
        }

        if ($user->can('viewOnlyOwnHostel:hostels')) {
            return ScopeLevelEnum::AssignedHostels;
        }

        if ($user->can('viewOnlyOwnDepartment:departments')) {
            if ($this->isHeadOfDivisionStaff()) {
                return ScopeLevelEnum::Division;
            }

            return ScopeLevelEnum::Department;
        }

        return ScopeLevelEnum::College;
    }

    /**
     * Null means unrestricted (college-wide). Empty array means no accessible departments.
     *
     * @return list<int>|null
     */
    public function departmentIds(): ?array
    {
        return match ($this->level()) {
            ScopeLevelEnum::Department => $this->staffDepartmentIds(),
            ScopeLevelEnum::Division => $this->divisionDepartmentIds(),
            default => null,
        };
    }

    /**
     * Null means unrestricted. Empty array means no assigned hostels.
     *
     * @return list<int>|null
     */
    public function hostelIds(): ?array
    {
        if ($this->level() !== ScopeLevelEnum::AssignedHostels) {
            return null;
        }

        $staff = $this->staff();

        if (! $staff instanceof Staff) {
            return [];
        }

        return Hostel::query()
            ->where('warden_id', $staff->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function isScopedToDepartments(): bool
    {
        return in_array($this->level(), [ScopeLevelEnum::Department, ScopeLevelEnum::Division], true);
    }

    public function isScopedToHostels(): bool
    {
        return $this->level() === ScopeLevelEnum::AssignedHostels;
    }

    /**
     * @return list<int>
     */
    private function staffDepartmentIds(): array
    {
        $staff = $this->staff();

        if (! $staff instanceof Staff) {
            return [];
        }

        return $staff->institutionDepartments()
            ->pluck('institution_departments.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function divisionDepartmentIds(): array
    {
        $staff = $this->staff();

        if (! $staff instanceof Staff) {
            return [];
        }

        $divisionIds = Division::query()
            ->where('head_of_division_id', $staff->id)
            ->pluck('id');

        if ($divisionIds->isEmpty()) {
            return $this->staffDepartmentIds();
        }

        return InstitutionDepartment::query()
            ->whereIn('division_id', $divisionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function isHeadOfDivisionStaff(): bool
    {
        $staff = $this->staff();

        if (! $staff instanceof Staff) {
            return false;
        }

        return Division::query()->where('head_of_division_id', $staff->id)->exists();
    }

    private function staff(): ?Staff
    {
        $user = $this->user;

        if (! $user instanceof User) {
            return null;
        }

        $staff = $user->staffProfile;

        return $staff instanceof Staff ? $staff : null;
    }
}

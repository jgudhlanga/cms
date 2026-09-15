<?php

namespace App\Support\Rbac;

use App\Enums\Rbac\ScopeLevelEnum;
use App\Models\HMS\Hostel;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Users\User;
use ArrayObject;

class UserAccessScope
{
    private const string REQUEST_CACHE = 'rbac.user-access-scopes';

    private ?ScopeLevelEnum $level = null;

    /** @var list<int>|null */
    private ?array $departmentIds = null;

    private bool $departmentIdsResolved = false;

    private ?bool $headOfDivision = null;

    public function __construct(private readonly ?User $user = null) {}

    /**
     * Reuses one scope per user object for the current request or queue job (a scoped binding), so
     * the staff, division and department lookups run once however many policies and helpers ask.
     */
    public static function for(?User $user = null): self
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return new self;
        }

        app()->scopedIf(self::REQUEST_CACHE, fn (): ArrayObject => new ArrayObject);

        /** @var ArrayObject<int|string, self> $scopes */
        $scopes = app(self::REQUEST_CACHE);
        $key = $user->getKey();

        if (! isset($scopes[$key]) || $scopes[$key]->user !== $user) {
            $scopes[$key] = new self($user);
        }

        return $scopes[$key];
    }

    /**
     * Drops memoized scopes, for when permissions or staff departments change within the same request
     * or process. Runs automatically after every HTTP request.
     */
    public static function flush(): void
    {
        app()->forgetInstance(self::REQUEST_CACHE);
    }

    public function level(): ScopeLevelEnum
    {
        return $this->level ??= $this->resolveLevel();
    }

    private function resolveLevel(): ScopeLevelEnum
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
        if (! $this->departmentIdsResolved) {
            $this->departmentIds = match ($this->level()) {
                ScopeLevelEnum::Department => $this->staffDepartmentIds(),
                ScopeLevelEnum::Division => $this->divisionDepartmentIds(),
                default => null,
            };
            $this->departmentIdsResolved = true;
        }

        return $this->departmentIds;
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

    /**
     * Whether the user may act on the given institution department. Root users and college-wide
     * users reach every department; department- and division-scoped users only reach their own.
     */
    public function canReachDepartment(int $institutionDepartmentId): bool
    {
        $user = $this->user;

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('root:manage')) {
            return true;
        }

        if ($institutionDepartmentId < 1) {
            return false;
        }

        $permitted = $this->departmentIds();

        // Null means unrestricted (college-wide); an empty array means no accessible departments.
        return $permitted === null || in_array($institutionDepartmentId, $permitted, true);
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
        if ($this->headOfDivision !== null) {
            return $this->headOfDivision;
        }

        $staff = $this->staff();

        return $this->headOfDivision = $staff instanceof Staff
            && Division::query()->where('head_of_division_id', $staff->id)->exists();
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

<?php

namespace App\Models\Users;

use App\Enums\Acl\PermissionEnum;
use App\Enums\Acl\RoleEnum;
use App\Http\Filters\Users\UserFilter;
use App\Models\Institution\Staff;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 *
 * @mixin Builder
 * @method static filter(UserFilter $filters)
 */
class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes,
        Filterable, Paginatable, LogsActivity, HasRoles, InteractsWithMedia, Impersonate;

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'email', 'password', 'tenant_id',
        'email_verified_at', 'last_login_at', 'login_count', 'avatar_id', 'status_id', 'phone_number',
    ];

    protected $appends = ['can_impersonate', 'can_be_impersonated', 'has_student_profile', 'has_staff_profile', 'avatar_url'];


    protected $hidden = ['password', 'remember_token'];

    const SUPER_ADMINISTRATOR = 1;


    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = bcrypt($password);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function isStudent(): bool
    {
        return $this->hasRole(RoleEnum::STUDENT->name());
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function getHasStaffProfileAttribute(): bool
    {
        return !is_null($this->staffProfile);
    }

    public function getHasStudentProfileAttribute(): bool
    {
        return !is_null($this->studentProfile);
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'ledgerable')->withTrashed();
    }

    public function getFullNameAttribute(): string
    {
        $nameParts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $nameParts);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('user-avatar')->singleFile();
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'avatar_id');
    }

    public function getAvatarUrlAttribute(): ?array
    {
        return ($this->avatar_id > 0) ? ['thumb' => $this->image->getFullUrl('thumb'), 'card' => $this->image->getFullUrl('card')] : null;
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'ledgerable');
    }

    public function canImpersonate(): bool
    {
        return $this->can(PermissionEnum::ROOT_MANAGE->value);
    }

    public function getCanImpersonateAttribute(): bool
    {
        return $this->canImpersonate();
    }

    public function getCanBeImpersonatedAttribute(): bool
    {
        return !$this->canImpersonate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('User')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

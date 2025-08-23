<?php

namespace App\Models\Users;

use App\Http\Filters\Users\UserFilter;
use App\Models\Institution\Staff;
use App\Models\Shared\Status;
use App\Models\Students\Student;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        Filterable, Paginatable, LogsActivity, HasRoles, InteractsWithMedia;

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'email', 'password', 'tenant_id',
        'email_verified_at', 'last_login_at', 'login_count', 'avatar_id', 'status_id', 'phone_number',
    ];

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

    public function studentProfile(): HasOne|User
    {
        return $this->hasOne(Student::class);
    }

    public function hasStudentProfile(): Attribute
    {
        return Attribute::get(fn() => $this->studentProfile()->exists());
    }

    public function staffProfile(): HasOne|User
    {
        return $this->hasOne(Staff::class);
    }

    public function hasStaffProfile(): Attribute
    {
        return Attribute::get(fn() => $this->staffProfile()->exists());
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('User')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

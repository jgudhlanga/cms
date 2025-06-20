<?php

namespace App\Models\Users;

use App\Http\Filters\Users\UserFilter;
use App\Models\Students\Student;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 *
 * @mixin Builder
 * @method static filter(UserFilter $filters)
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Filterable, Paginatable, LogsActivity, HasRoles;

    protected $fillable = ['first_name', 'middle_name', 'last_name', 'email', 'password', 'tenant_id', 'email_verified_at'];

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

    public function studentProfile(): HasOne|User
    {
        return $this->hasOne(Student::class);
    }

    public function hasStudentProfile(): Attribute
    {
        return Attribute::get(fn() => $this->studentProfile()->exists());
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

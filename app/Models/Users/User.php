<?php

namespace App\Models\Users;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Http\Filters\Users\UserFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

/**
 *
 * @mixin Builder
 * @method static filter(UserFilter $filters)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Filterable, Paginatable, LogsActivity, HasRoles;

    protected $fillable = [ 'name', 'email', 'password', 'tenant_id' ];

    protected $hidden = [ 'password', 'remember_token' ];

	const SUPER_USER = 1;
    protected function casts(): array
    {
        return [ 'email_verified_at' => 'datetime',  'password' => 'hashed' ];
    }

	public function setPasswordAttribute(string $password): void
	{
		$this->attributes['password'] = bcrypt($password);
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

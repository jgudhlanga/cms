<?php

namespace App\Repositories\Applications;

use App\DTO\Portal\ApplicationDto;
use App\Http\Filters\Portal\ApplicationFilter;
use App\Models\Applications\Application;
use App\Repositories\Applications\interface\IApplicationRepository;
use App\Repositories\Base\BaseRepository;

class ApplicationRepository extends BaseRepository implements IApplicationRepository
{
	public function __construct(protected Application $application)
	{
		parent::__construct($this->application);
	}

	public function create(ApplicationDto $dto): Application
	{
        return $this->application->create([]);
	}

	public function update(Application $application, ApplicationDto $dto): Application
	{
        return tap($application)->update([
        ]);
	}

	public function allFilter($columns = ['*'], ApplicationFilter $filters = null)
	{
		return $this->application
			->select($columns)
			->filter($filters)
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}

}

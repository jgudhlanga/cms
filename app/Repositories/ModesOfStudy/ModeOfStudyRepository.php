<?php

namespace App\Repositories\ModesOfStudy;

use App\DTO\Institution\ModeOfStudyDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Base\BaseRepository;
use App\Repositories\ModesOfStudy\interface\IModeOfStudyRepository;

class ModeOfStudyRepository extends BaseRepository implements IModeOfStudyRepository
{
    public function __construct(protected ModeOfStudy $modeOfStudy)
    {
        parent::__construct($this->modeOfStudy);
    }

    public function create(ModeOfStudyDto $dto): ModeOfStudy
    {
        return $this->modeOfStudy->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(ModeOfStudy $modeOfStudy, ModeOfStudyDto $dto): ModeOfStudy
    {
        return tap($modeOfStudy)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->modeOfStudy
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}

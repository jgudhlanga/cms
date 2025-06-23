<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\RelationshipDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\Relationship;
use App\Repositories\Base\Interface\IBaseRepository;

interface IRelationshipRepository extends IBaseRepository
{
    public function create(RelationshipDto $dto);

    public function update(Relationship $relationship, RelationshipDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}

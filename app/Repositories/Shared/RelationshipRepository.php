<?php

namespace App\Repositories\Shared;

use App\DTO\Relationships\RelationshipDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\Relationship;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IRelationshipRepository;

class RelationshipRepository extends BaseRepository implements IRelationshipRepository
{
    public function __construct(protected Relationship $relationship)
    {
        parent::__construct($this->relationship);
    }

    public function create(RelationshipDto $dto): Relationship
    {
        return $this->relationship->create([
            'name' => $dto->name,
            'description' => $dto->description,
        ])->refresh();
    }

    public function update(Relationship $relationship, RelationshipDto $dto): Relationship
    {
        return tap($relationship)->update([
            'name' => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
    {
        return $this->relationship
            ->select($columns)
            ->filter($filters)
            ->orderBy('name')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }
}

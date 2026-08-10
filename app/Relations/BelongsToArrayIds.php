<?php

namespace App\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Many-to-many style relation where related IDs are stored as an array on the parent.
 *
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends Relation<TRelatedModel, TDeclaringModel, EloquentCollection<int, TRelatedModel>>
 */
class BelongsToArrayIds extends Relation
{
    public function __construct(
        Builder $query,
        Model $parent,
        protected string $localKey,
        protected string $relatedKey = 'id',
    ) {
        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        if (! static::$constraints) {
            return;
        }

        $ids = $this->getParentIds($this->parent);

        if ($ids === []) {
            $this->query->whereRaw('0 = 1');

            return;
        }

        $this->query->whereIn(
            $this->related->qualifyColumn($this->relatedKey),
            $ids
        );
    }

    /**
     * @param  array<int, TDeclaringModel>  $models
     */
    public function addEagerConstraints(array $models): void
    {
        $ids = $this->getEagerIds($models);

        $this->whereInEager(
            $this->whereInMethod($this->related, $this->relatedKey),
            $this->related->qualifyColumn($this->relatedKey),
            $ids
        );
    }

    /**
     * @param  array<int, TDeclaringModel>  $models
     * @return array<int, TDeclaringModel>
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * @param  array<int, TDeclaringModel>  $models
     * @param  EloquentCollection<int, TRelatedModel>  $results
     * @return array<int, TDeclaringModel>
     */
    public function match(array $models, EloquentCollection $results, $relation): array
    {
        /** @var array<string, TRelatedModel> $dictionary */
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[(string) $result->getAttribute($this->relatedKey)] = $result;
        }

        foreach ($models as $model) {
            $matched = [];

            foreach ($this->getParentIds($model) as $id) {
                $key = (string) $id;
                if (isset($dictionary[$key])) {
                    $matched[] = $dictionary[$key];
                }
            }

            $model->setRelation($relation, $this->related->newCollection($matched));
        }

        return $models;
    }

    /**
     * @return EloquentCollection<int, TRelatedModel>
     */
    public function getResults(): EloquentCollection
    {
        $ids = $this->getParentIds($this->parent);

        if ($ids === []) {
            return $this->related->newCollection();
        }

        $results = $this->query->get();

        /** @var array<string, TRelatedModel> $dictionary */
        $dictionary = $results->keyBy(
            fn (Model $model): string => (string) $model->getAttribute($this->relatedKey)
        )->all();

        $ordered = [];
        foreach ($ids as $id) {
            $key = (string) $id;
            if (isset($dictionary[$key])) {
                $ordered[] = $dictionary[$key];
            }
        }

        return $this->related->newCollection($ordered);
    }

    /**
     * @param  array<int, Model>  $models
     * @return list<int|string>
     */
    protected function getEagerIds(array $models): array
    {
        $ids = [];

        foreach ($models as $model) {
            foreach ($this->getParentIds($model) as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<int|string>
     */
    protected function getParentIds(Model $model): array
    {
        $value = $model->getAttribute($this->localKey);

        if (! is_array($value) || $value === []) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $id): bool => $id !== null && $id !== ''
        ));
    }
}

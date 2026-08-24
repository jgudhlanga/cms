<?php

namespace App\Http\Resources\AuditTrail;

use App\Models\Users\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class AuditTrailResource extends JsonResource
{
    /**
     * @var array<class-string<Model>, array<string, string>>
     */
    private static array $belongsToRelationsCache = [];

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
    ];

    public function toArray(Request $request): array
    {
        $bag = $this->asArray($this->properties);

        return [
            'type' => 'audit-trail',
            'id' => $this->resource->id,
            'attributes' => [
                'logName' => $this->resource->log_name,
                'description' => $this->resource->description,
                'subjectType' => $this->resource->subject_type,
                'subjectId' => $this->resource->subject_id,
                'causerType' => $this->resource->causer_type,
                'causer' => $this->getCauserName(),
                'properties' => $this->formatProperties($this->asArray($bag['attributes'] ?? [])),
                'oldProperties' => $this->formatProperties($this->asArray($bag['old'] ?? [])),
                'batchUuid' => $this->resource->batch_uuid,
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
            ],
        ];
    }

    private function getCauserName(): string
    {
        return User::find($this->resource->causer_id)?->full_name
            ?? User::find(User::SUPER_ADMINISTRATOR)?->full_name
            ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function asArray(mixed $value): array
    {
        if ($value instanceof Collection) {
            return $value->toArray();
        }

        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function formatProperties(array $properties): array
    {
        return $this->resolveForeignKeyProperties(
            $this->sanitizeProperties($properties)
        );
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function sanitizeProperties(array $properties): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            unset($properties[$key]);
        }

        return $properties;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function resolveForeignKeyProperties(array $properties): array
    {
        $subject = $this->subjectForRelations();

        if (! $subject instanceof Model) {
            return $properties;
        }

        foreach ($properties as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_id') || ! $this->isResolvableForeignKeyValue($value)) {
                continue;
            }

            $relationName = $this->relationNameForForeignKey($subject, $key);

            if (! is_string($relationName)) {
                continue;
            }

            $resolvedValue = $this->resolveRelationLabel($subject, $relationName, $value);

            if ($resolvedValue !== null) {
                $properties[$key] = $resolvedValue;
            }
        }

        return $properties;
    }

    private function subjectForRelations(): ?Model
    {
        return $this->resolveSubject() ?? $this->newSubjectInstance();
    }

    private function resolveSubject(): ?Model
    {
        if ($this->resource->relationLoaded('subject')) {
            $subject = $this->resource->getRelation('subject');

            if ($subject instanceof Model) {
                return $subject;
            }
        }

        $subjectType = $this->resource->subject_type;
        $subjectId = $this->resource->subject_id;

        if (! is_string($subjectType) || ! is_subclass_of($subjectType, Model::class) || $subjectId === null) {
            return null;
        }

        /** @var class-string<Model> $subjectType */
        try {
            $query = $subjectType::query();

            if ($this->modelUsesSoftDeletes($subjectType)) {
                $query->withTrashed();
            }

            $found = $query->find($subjectId);
        } catch (Throwable) {
            return null;
        }

        return $found instanceof Model ? $found : null;
    }

    private function newSubjectInstance(): ?Model
    {
        $subjectType = $this->resource->subject_type;

        if (! is_string($subjectType) || ! is_subclass_of($subjectType, Model::class)) {
            return null;
        }

        try {
            return new $subjectType;
        } catch (Throwable) {
            return null;
        }
    }

    private function relationNameForForeignKey(Model $subject, string $foreignKey): ?string
    {
        $mapped = $this->belongsToRelationsByForeignKey($subject)[$foreignKey] ?? null;

        if (is_string($mapped)) {
            return $mapped;
        }

        $guess = Str::camel(Str::beforeLast($foreignKey, '_id'));

        if ($guess === '' || ! method_exists($subject, $guess)) {
            return null;
        }

        try {
            $relation = $subject->{$guess}();
        } catch (Throwable) {
            return null;
        }

        return $relation instanceof BelongsTo ? $guess : null;
    }

    /**
     * @return array<string, string>
     */
    private function belongsToRelationsByForeignKey(Model $subject): array
    {
        $class = $subject::class;

        if (isset(self::$belongsToRelationsCache[$class])) {
            return self::$belongsToRelationsCache[$class];
        }

        $relations = [];
        $reflection = new ReflectionClass($subject);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();

            if (! $returnType instanceof ReflectionNamedType || $returnType->isBuiltin()) {
                continue;
            }

            if (! is_a($returnType->getName(), BelongsTo::class, true)) {
                continue;
            }

            try {
                $relation = $subject->{$method->getName()}();
            } catch (Throwable) {
                continue;
            }

            if (! $relation instanceof BelongsTo) {
                continue;
            }

            $relations[$relation->getForeignKeyName()] = $method->getName();
        }

        return self::$belongsToRelationsCache[$class] = $relations;
    }

    private function isResolvableForeignKeyValue(mixed $value): bool
    {
        if (is_int($value) && $value > 0) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        return $trimmed !== '' && ctype_digit($trimmed);
    }

    private function resolveRelationLabel(Model $subject, string $relationName, mixed $foreignKeyValue): ?string
    {
        try {
            $relation = $subject->{$relationName}();

            if (! $relation instanceof BelongsTo) {
                return null;
            }

            $related = $this->findRelated($relation, $foreignKeyValue);
        } catch (Throwable) {
            return null;
        }

        if (! $related instanceof Model) {
            return null;
        }

        return $this->extractModelLabel($related);
    }

    private function findRelated(BelongsTo $relation, mixed $foreignKeyValue): ?Model
    {
        $related = $relation->getRelated();
        $query = $related->newQuery();

        if ($this->modelUsesSoftDeletes($related::class)) {
            $query->withTrashed();
        }

        $found = $query->find($foreignKeyValue);

        return $found instanceof Model ? $found : null;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function modelUsesSoftDeletes(string $class): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    private function extractModelLabel(Model $model): ?string
    {
        foreach (['full_name', 'name', 'title', 'label', 'description', 'department_code', 'code', 'email'] as $attribute) {
            $label = $this->stringifyLabel($model->getAttribute($attribute));

            if ($label !== null) {
                return $label;
            }
        }

        $combined = trim(implode(' ', array_filter([
            $this->stringifyLabel($model->getAttribute('first_name')),
            $this->stringifyLabel($model->getAttribute('last_name')),
        ])));

        return $combined !== '' ? $combined : null;
    }

    private function stringifyLabel(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}

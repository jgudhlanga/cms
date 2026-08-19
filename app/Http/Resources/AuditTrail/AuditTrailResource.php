<?php

namespace App\Http\Resources\AuditTrail;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        $properties = $this->properties['attributes'] ?? [];

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
                'properties' => $this->formatProperties(is_array($properties) ? $properties : []),
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
        $subject = $this->resolveSubject();

        if (! $subject instanceof Model) {
            return $properties;
        }

        $relationsByForeignKey = $this->belongsToRelationsByForeignKey($subject);

        foreach ($properties as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_id') || ! $this->isResolvableForeignKeyValue($value)) {
                continue;
            }

            $relationName = $relationsByForeignKey[$key] ?? null;

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

    private function resolveSubject(): ?Model
    {
        if ($this->resource->relationLoaded('subject')) {
            $subject = $this->resource->getRelation('subject');

            return $subject instanceof Model ? $subject : null;
        }

        $subjectType = $this->resource->subject_type;
        $subjectId = $this->resource->subject_id;

        if (! is_string($subjectType) || ! is_subclass_of($subjectType, Model::class) || $subjectId === null) {
            return null;
        }

        /** @var class-string<Model> $subjectType */
        return $subjectType::query()->find($subjectId);
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
        if (is_int($value)) {
            return true;
        }

        return is_string($value) && trim($value) !== '';
    }

    private function resolveRelationLabel(Model $subject, string $relationName, mixed $foreignKeyValue): ?string
    {
        try {
            $relation = $subject->{$relationName}();

            if (! $relation instanceof BelongsTo) {
                return null;
            }

            $related = $relation->getRelated()->newQuery()->find($foreignKeyValue);
        } catch (Throwable) {
            return null;
        }

        if (! $related instanceof Model) {
            return null;
        }

        return $this->extractModelLabel($related);
    }

    private function extractModelLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'full_name', 'description', 'department_code', 'code'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            if (is_int($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}

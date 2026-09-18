<?php

declare(strict_types=1);

namespace App\Support\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;

/**
 * Reads the command allowlist in config/custom/console.php and turns it into
 * front-end payloads, validation rules, and Artisan parameter arrays.
 *
 * @phpstan-type ParamDefinition array{name: string, type: string, required?: bool, default?: mixed, options?: list<string>, min?: int, max?: int, expands_to_flag?: bool}
 * @phpstan-type CommandDefinition array{key: string, group: string, signature: string, risk: string, timeout: int, pinned: array<string, mixed>, arguments: list<ParamDefinition>, options: list<ParamDefinition>}
 */
class ConsoleCommandRegistry
{
    public const RISK_READ_ONLY = 'read_only';

    public const RISK_MUTATING = 'mutating';

    public const RISK_DESTRUCTIVE = 'destructive';

    /**
     * The allowlist keyed by command key, flattened out of its groups.
     *
     * @var array<string, CommandDefinition>|null
     */
    private ?array $commands = null;

    /**
     * Groups with their commands, translated and ready for the Inertia page.
     *
     * @return list<array<string, mixed>>
     */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->config() as $groupKey => $group) {
            $commands = [];

            foreach (array_keys($group['commands'] ?? []) as $commandKey) {
                $commands[] = $this->present((string) $commandKey);
            }

            if ($commands === []) {
                continue;
            }

            $groups[] = [
                'key' => $groupKey,
                'label' => __('console.group_'.str_replace('-', '_', (string) $groupKey)),
                'icon' => $group['icon'] ?? 'terminal',
                'commands' => $commands,
            ];
        }

        return $groups;
    }

    /**
     * @return CommandDefinition|null
     */
    public function find(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function isDestructive(string $key): bool
    {
        return ($this->find($key)['risk'] ?? null) === self::RISK_DESTRUCTIVE;
    }

    public function timeout(string $key): int
    {
        return (int) ($this->find($key)['timeout'] ?? 300);
    }

    /**
     * Validation rules for the `parameters` payload of a single command.
     *
     * @return array<string, mixed>
     */
    public function rules(string $key): array
    {
        $definition = $this->find($key);

        if ($definition === null) {
            return [];
        }

        $rules = [];

        foreach ([...$definition['arguments'], ...$definition['options']] as $param) {
            $rules['parameters.'.$param['name']] = $this->rulesForParam($param);
        }

        return $rules;
    }

    /**
     * Turn validated user input into the array Artisan::call expects.
     *
     * Blank values are dropped so the command falls back to its own defaults,
     * boolean flags are only passed when on, and pinned values always win.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function toArtisanParameters(string $key, array $input): array
    {
        $definition = $this->find($key);

        if ($definition === null) {
            return [];
        }

        $parameters = [];

        foreach ($definition['arguments'] as $param) {
            $value = $this->castValue($param, $input[$param['name']] ?? null);

            if ($value !== null) {
                $parameters[$param['name']] = $value;
            }
        }

        foreach ($definition['options'] as $param) {
            $value = $this->castValue($param, $input[$param['name']] ?? null);

            if ($value === null) {
                continue;
            }

            // A radio whose value is itself the flag, e.g. --dry-run vs --execute.
            if (($param['expands_to_flag'] ?? false) === true) {
                $parameters[(string) $value] = true;

                continue;
            }

            if ($param['type'] === 'boolean') {
                if ($value === true) {
                    $parameters[$param['name']] = true;
                }

                continue;
            }

            $parameters[$param['name']] = $value;
        }

        return [...$parameters, ...$definition['pinned']];
    }

    /**
     * A human-readable preview of the command line a run will execute.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function commandLine(string $key, array $parameters): string
    {
        $definition = $this->find($key);

        if ($definition === null) {
            return '';
        }

        $parts = ['php artisan', $definition['signature']];

        foreach ($parameters as $name => $value) {
            if (str_starts_with((string) $name, '--')) {
                $parts[] = $value === true ? $name : $name.'='.$value;

                continue;
            }

            $parts[] = (string) $value;
        }

        return implode(' ', $parts);
    }

    /**
     * Basenames of the CSV files a `file` parameter can choose from.
     *
     * @return list<string>
     */
    public function csvFiles(): array
    {
        $path = $this->csvPath();

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'csv')
            ->map(fn ($file) => $file->getFilename())
            ->sort()
            ->values()
            ->all();
    }

    public function csvPath(): string
    {
        return (string) config('custom.console.csv_path', storage_path('app/mode-restore'));
    }

    public function queue(): string
    {
        return (string) config('custom.console.queue', 'default');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(string $key): array
    {
        $definition = $this->all()[$key];
        $slug = str_replace('-', '_', $key);

        return [
            'key' => $key,
            'signature' => $definition['signature'],
            'label' => __('console.cmd_'.$slug),
            'summary' => __('console.cmd_'.$slug.'_summary'),
            'details' => __('console.cmd_'.$slug.'_details'),
            'risk' => $definition['risk'],
            'arguments' => array_map(fn (array $param) => $this->presentParam($slug, $param), $definition['arguments']),
            'options' => array_map(fn (array $param) => $this->presentParam($slug, $param), $definition['options']),
        ];
    }

    /**
     * @param  ParamDefinition  $param
     * @return array<string, mixed>
     */
    private function presentParam(string $commandSlug, array $param): array
    {
        $paramSlug = str_replace('-', '_', ltrim($param['name'], '-'));

        return [
            'name' => $param['name'],
            'type' => $param['type'],
            'required' => $param['required'] ?? false,
            'default' => $param['default'] ?? null,
            'label' => __('console.param_'.$commandSlug.'_'.$paramSlug),
            'hint' => Lang::has($hintKey = 'console.param_'.$commandSlug.'_'.$paramSlug.'_hint')
                ? __($hintKey)
                : null,
            'choices' => $param['type'] === 'file' ? $this->csvFiles() : ($param['options'] ?? []),
            'min' => $param['min'] ?? null,
            'max' => $param['max'] ?? null,
        ];
    }

    /**
     * @param  ParamDefinition  $param
     * @return list<mixed>
     */
    private function rulesForParam(array $param): array
    {
        $required = ($param['required'] ?? false) === true;

        $rules = [$required ? 'required' : 'nullable'];

        switch ($param['type']) {
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'integer':
                $rules[] = 'integer';

                if (isset($param['min'])) {
                    $rules[] = 'min:'.$param['min'];
                }

                if (isset($param['max'])) {
                    $rules[] = 'max:'.$param['max'];
                }
                break;
            case 'date':
                $rules[] = 'date_format:Y-m-d';
                break;
            case 'select':
            case 'radio':
                $rules[] = 'string';
                $rules[] = Rule::in($param['options'] ?? []);
                break;
            case 'file':
                $rules[] = 'string';
                $rules[] = Rule::in($this->csvFiles());
                break;
            default:
                $rules[] = 'string';
                $rules[] = 'max:'.($param['max'] ?? 255);
        }

        return $rules;
    }

    /**
     * @param  ParamDefinition  $param
     */
    private function castValue(array $param, mixed $value): mixed
    {
        if ($param['type'] === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return match ($param['type']) {
            'integer' => (int) $value,
            // Re-resolve against the configured directory so a path cannot escape it.
            'file' => $this->csvPath().DIRECTORY_SEPARATOR.basename((string) $value),
            default => (string) $value,
        };
    }

    /**
     * @return array<string, CommandDefinition>
     */
    private function all(): array
    {
        if ($this->commands !== null) {
            return $this->commands;
        }

        $commands = [];

        foreach ($this->config() as $groupKey => $group) {
            foreach ($group['commands'] ?? [] as $commandKey => $command) {
                $commands[(string) $commandKey] = [
                    'key' => (string) $commandKey,
                    'group' => (string) $groupKey,
                    'signature' => (string) $command['signature'],
                    'risk' => (string) ($command['risk'] ?? self::RISK_MUTATING),
                    'timeout' => (int) ($command['timeout'] ?? 300),
                    'pinned' => (array) ($command['pinned'] ?? []),
                    'arguments' => array_values((array) ($command['arguments'] ?? [])),
                    'options' => array_values((array) ($command['options'] ?? [])),
                ];
            }
        }

        return $this->commands = $commands;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function config(): array
    {
        return (array) config('custom.console.groups', []);
    }
}

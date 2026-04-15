<?php

namespace App\Http\Requests\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @property mixed $level
 */
class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:levels,name,'.$this->level?->id],
            'calendar_type' => ['required', new Enum(AcademicCalendarTypeEnum::class)],
        ];
    }
}

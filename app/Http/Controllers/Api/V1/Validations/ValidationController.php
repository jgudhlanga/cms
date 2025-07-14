<?php

namespace App\Http\Controllers\Api\V1\Validations;

use App\Http\Controllers\Controller;
use App\Models\Institution\Staff;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    protected array $validationMap = [
        'user_email' => [User::class, 'email'],
        'user_phone_number' => [User::class, 'phone_number'],
        'staff_national_id' => [Staff::class, 'id_number'],
        'staff_passport_number' => [Staff::class, 'passport_number'],
        'staff_employee_number' => [Staff::class, 'employee_number'],
        'student_national_id' => [Student::class, 'id_number'],
        'student_passport_number' => [Student::class, 'passport_number'],
    ];

    public function check(Request $request)
    {
        $key = $request->query('key');
        $value = $request->query('value');
        $currentId = $request->query('current_id');

        if (!isset($this->validationMap[$key]) || empty($value)) {
            return response()->json([
                'error' => 'Invalid key or value missing.'
            ], 422);
        }

        [$model, $column] = $this->validationMap[$key];

        $query = $model::where($column, $value);

        if (!empty($currentId)) {
            $keyName = (new $model)->getKeyName(); // usually 'id'
            $query->where($keyName, '!=', $currentId);
        }

        $exists = $query->exists();

        return response()->json(['available' => !$exists]);
    }
}

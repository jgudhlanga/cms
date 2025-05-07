<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DepartmentMetaDataController extends Controller
{

    public function __invoke(Request $request)
    {
        return response()->json(['name' => 'James Gudhlanga']);
    }
}

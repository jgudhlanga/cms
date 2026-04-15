<?php

namespace App\Http\Controllers\Api\V1\Preferences;

use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\UserPreferenceRequest;
use App\Http\Resources\Preferences\UserPreferenceResource;
use App\Models\Preferences\UserPreference;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function index(Request $request): UserPreferenceResource
    {
        $preference = UserPreference::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['side_bar_state' => false],
        );

        return UserPreferenceResource::make($preference);
    }

    public function store(UserPreferenceRequest $request): UserPreferenceResource
    {
        $preference = UserPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['side_bar_state' => $request->boolean('side_bar_state')],
        );

        return UserPreferenceResource::make($preference);
    }

    public function update(UserPreferenceRequest $request, UserPreference $preference): UserPreferenceResource
    {
        if ($preference->user_id !== $request->user()->id) {
            abort(403);
        }

        $preference->update([
            'side_bar_state' => $request->boolean('side_bar_state'),
        ]);

        return UserPreferenceResource::make($preference);
    }
}

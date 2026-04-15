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
            ['side_bar_state' => false, 'locale' => 'en'],
        );

        return UserPreferenceResource::make($preference);
    }

    public function store(UserPreferenceRequest $request): UserPreferenceResource
    {
        $payload = $this->validatedPayload($request);

        $preference = UserPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            array_merge(['side_bar_state' => false, 'locale' => 'en'], $payload),
        );

        return UserPreferenceResource::make($preference);
    }

    public function update(UserPreferenceRequest $request, UserPreference $preference): UserPreferenceResource
    {
        if ($preference->user_id !== $request->user()->id) {
            abort(403);
        }

        $preference->update($this->validatedPayload($request));

        return UserPreferenceResource::make($preference);
    }

    private function validatedPayload(UserPreferenceRequest $request): array
    {
        $validated = $request->validated();
        $payload = [];

        if (array_key_exists('side_bar_state', $validated)) {
            $payload['side_bar_state'] = (bool) $validated['side_bar_state'];
        }

        if (array_key_exists('locale', $validated)) {
            $payload['locale'] = $validated['locale'];
        }

        return $payload;
    }
}

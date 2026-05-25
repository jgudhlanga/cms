<?php

namespace App\Http\Resources\Preferences;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user-preferences',
            'id' => $this->id,
            'attributes' => [
                'userId' => $this->user_id,
                'sideBarState' => (bool) $this->side_bar_state,
                'locale' => $this->locale,
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'financeExchangeRate',
            'id' => $this->resource->id,
            'attributes' => [
                'date' => $this->resource->date,
                'currencyFrom' => $this->resource->currency_from,
                'currencyTo' => $this->resource->currency_to,
                'rate' => $this->resource->rate,
                $this->mergeWhen($request->routeIs('finance.exchange-rates.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}

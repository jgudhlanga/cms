<?php

namespace App\Repositories\HMS;

use App\Models\HMS\HostelAmenity;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelAmenityRepository;

class HostelAmenityRepository extends BaseRepository implements IHostelAmenityRepository
{
    public function __construct(protected HostelAmenity $hostelAmenity)
    {
        parent::__construct($this->hostelAmenity);
    }

    public function create(array $data): HostelAmenity
    {
        return $this->hostelAmenity->create([
            'name' => trim((string) ($data['name'] ?? '')),
            'market_value' => $this->resolveMarketValue($data),
        ])->refresh();
    }

    public function update(HostelAmenity $hostelAmenity, array $data): HostelAmenity
    {
        $hostelAmenity->update([
            'name' => trim((string) ($data['name'] ?? '')),
            'market_value' => $this->resolveMarketValue($data),
        ]);

        return $hostelAmenity->refresh();
    }

    private function resolveMarketValue(array $data): ?float
    {
        if (! array_key_exists('market_value', $data) || $data['market_value'] === null || $data['market_value'] === '') {
            return null;
        }

        return (float) $data['market_value'];
    }
}

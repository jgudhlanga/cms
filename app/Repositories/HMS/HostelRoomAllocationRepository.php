<?php

namespace App\Repositories\HMS;

use App\Models\HMS\HostelRoomAllocation;
use App\Repositories\Base\BaseRepository;
use App\Repositories\HMS\interface\IHostelRoomAllocationRepository;

class HostelRoomAllocationRepository extends BaseRepository implements IHostelRoomAllocationRepository
{
    public function __construct(protected HostelRoomAllocation $allocation)
    {
        parent::__construct($this->allocation);
    }
}

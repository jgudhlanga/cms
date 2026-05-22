<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\HostelRoomAllocations\HostelRoomAllocationSchema;
use App\JsonApi\V1\HostelRooms\HostelRoomSchema;
use App\JsonApi\V1\Hostels\HostelSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    protected string $baseUri = '/api/v1/json';

    public function serving(): void
    {
        // Tenant scoping is applied via BelongsToTenant global scope on HMS models.
    }

    protected function allSchemas(): array
    {
        return [
            HostelSchema::class,
            HostelRoomSchema::class,
            HostelRoomAllocationSchema::class,
        ];
    }
}
